/**
 * InstantCMS multi-file uploader (HTML5, no dependencies).
 *
 * Replacement for the retired SWFUpload/Flash uploader. Wire-compatible
 * with the legacy endpoints: each file is POSTed to uploadUrl as the
 * `fileField` field together with `params`, the server responds with
 * "FILEID:<id>" on success or HTTP 500 on failure.
 *
 * Usage:
 *   new MultiUpload({
 *       uploadUrl: '/components/photos/ajax/upload_photo.php',
 *       params:    { sess_id: '...', album_id: 5 },
 *       accept:    '.jpg,.jpeg,.png,.gif',
 *       maxFiles:  100,
 *       maxBytes:  20971520,
 *       buttonLabel: 'Upload',
 *       buttonTarget: 'spanButtonPlaceHolder',
 *       progressTarget: 'fsUploadProgress',
 *       cancelBtn: 'btnCancel',
 *       onQueueComplete: function (uploaded) { ... }
 *   });
 */
function MultiUpload(opts) {
    this.opts = opts;
    this.queue = [];
    this.uploaded = 0;
    this.aborted = false;
    this.xhr = null;

    this.progressBox = document.getElementById(opts.progressTarget);
    this.cancelBtn = document.getElementById(opts.cancelBtn);

    this.buildButton();
    if (this.cancelBtn) {
        var self = this;
        this.cancelBtn.onclick = function () { self.cancelQueue(); };
    }
}

MultiUpload.prototype.buildButton = function () {
    var self = this;
    var host = document.getElementById(this.opts.buttonTarget);
    if (!host) { return; }

    var input = document.createElement('input');
    input.type = 'file';
    input.multiple = true;
    if (this.opts.accept) { input.accept = this.opts.accept; }
    input.style.display = 'none';

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'mu-select-btn';
    btn.innerHTML = this.opts.buttonLabel || 'Upload';
    btn.onclick = function () { input.click(); };

    input.onchange = function () {
        self.enqueue(input.files);
        input.value = '';
    };

    host.appendChild(btn);
    host.appendChild(input);
};

MultiUpload.prototype.enqueue = function (files) {
    var limit = this.opts.maxFiles || 100;
    var maxBytes = this.opts.maxBytes || 20 * 1024 * 1024;

    for (var i = 0; i < files.length && this.uploaded + this.queue.length < limit; i++) {
        var file = files[i];
        if (file.size > maxBytes) {
            this.addRow(file.name, 0, this.opts.langTooLarge || 'File is too large', true);
            continue;
        }
        this.queue.push(file);
    }

    if (this.queue.length) { this.next(); }
};

MultiUpload.prototype.next = function () {
    if (this.aborted || !this.queue.length) { return; }

    var self = this;
    var file = this.queue[0];
    var row = this.addRow(file.name);

    var form = new FormData();
    var params = this.opts.params || {};
    for (var key in params) {
        if (Object.prototype.hasOwnProperty.call(params, key)) {
            form.append(key, params[key]);
        }
    }
    form.append(this.opts.fileField || 'Filedata', file, file.name);

    var xhr = new XMLHttpRequest();
    this.xhr = xhr;
    xhr.open('POST', this.opts.uploadUrl, true);
    xhr.overrideMimeType('text/plain; charset=utf-8');

    xhr.upload.onprogress = function (e) {
        if (e.lengthComputable) {
            self.setProgress(row, Math.round((e.loaded / e.total) * 100));
        }
    };

    xhr.onload = function () {
        var ok = xhr.status === 200 && String(xhr.responseText).indexOf('FILEID:') === 0;
        self.finishRow(row, ok, xhr.status);
        self.queue.shift();
        if (!self.aborted) { self.next(); }
        self.maybeComplete();
    };

    xhr.onerror = function () {
        self.finishRow(row, false, 0);
        self.queue.shift();
        if (!self.aborted) { self.next(); }
        self.maybeComplete();
    };

    if (this.cancelBtn) { this.cancelBtn.disabled = false; }
    xhr.send(form);
};

MultiUpload.prototype.addRow = function (name, progress, status, failed) {
    if (!this.progressBox) { return null; }
    this.progressBox.style.display = 'block';

    var row = document.createElement('div');
    row.className = 'mu-row' + (failed ? ' mu-fail' : '');

    var label = document.createElement('span');
    label.className = 'mu-name';
    label.innerHTML = this.escape(name) + (status ? ' — ' + this.escape(status) : '');

    var bar = document.createElement('div');
    bar.className = 'mu-bar';
    var fill = document.createElement('div');
    fill.className = 'mu-fill';
    fill.style.width = (progress || 0) + '%';
    bar.appendChild(fill);

    row.appendChild(label);
    row.appendChild(bar);
    this.progressBox.appendChild(row);

    row._fill = fill;
    return row;
};

MultiUpload.prototype.setProgress = function (row, percent) {
    if (row && row._fill) { row._fill.style.width = percent + '%'; }
};

MultiUpload.prototype.finishRow = function (row, ok, status) {
    if (!row) { return; }
    row.className += ok ? ' mu-ok' : ' mu-fail';
    this.setProgress(row, 100);
    if (!ok) {
        var label = row.getElementsByTagName('span')[0];
        if (label) {
            label.innerHTML += ' — ' + (this.opts.langError || 'Upload error') +
                (status ? ' (' + status + ')' : '');
        }
    }
};

MultiUpload.prototype.cancelQueue = function () {
    this.aborted = true;
    this.queue = [];
    if (this.xhr) { this.xhr.abort(); }
    if (this.cancelBtn) { this.cancelBtn.disabled = true; }
};

MultiUpload.prototype.maybeComplete = function () {
    if (this.aborted || this.queue.length) { return; }
    if (this.cancelBtn) { this.cancelBtn.disabled = true; }
    if (this.uploaded > 0 && this.opts.onQueueComplete) {
        this.opts.onQueueComplete(this.uploaded);
    }
};

MultiUpload.prototype.escape = function (text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
};
