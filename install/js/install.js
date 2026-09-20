/**
 * InstantCMS installer — two-column wizard (5 steps).
 * Vanilla JS: stepper, gating, validation, password meter, DB check, summary.
 */
(function () {
    'use strict';

    var L = (window.INSTALL && window.INSTALL.langJS) || {};
    var current = 1;
    var total = 5;
    var dbOk = false;

    /* ------------------------------------------------------------- тема */

    function initTheme() {
        var saved = null;
        try { saved = localStorage.getItem('inst_theme'); } catch (e) {}
        if (saved === 'dark') { document.body.setAttribute('data-theme', 'dark'); }
        var btn = document.getElementById('theme-toggle');
        if (btn) {
            btn.addEventListener('click', function () {
                var dark = document.body.getAttribute('data-theme') === 'dark';
                if (dark) { document.body.removeAttribute('data-theme'); }
                else { document.body.setAttribute('data-theme', 'dark'); }
                try { localStorage.setItem('inst_theme', dark ? 'light' : 'dark'); } catch (e) {}
            });
        }
    }

    /* ------------------------------------------------------------- язык */

    function initLang() {
        var btn = document.getElementById('langs-btn');
        var list = document.getElementById('langs-list');
        if (!btn || !list) { return; }
        btn.addEventListener('click', function (e) { e.stopPropagation(); list.hidden = !list.hidden; });
        document.addEventListener('click', function () { list.hidden = true; });
        list.addEventListener('click', function (e) {
            var li = e.target.closest('li');
            if (!li || li.classList.contains('is-current')) { return; }
            var input = document.getElementById('langform-input');
            if (input) { input.value = li.getAttribute('data-lang'); document.getElementById('langform').submit(); }
        });
    }

    /* ------------------------------------------------------------- степпер */

    function stepEl(n) { return document.querySelector('.step[data-step="' + n + '"]'); }

    function nextBtn(step) { return step ? step.querySelector('[data-nav="next"]') : null; }

    function showStep(n) {
        current = n;
        document.querySelectorAll('.step').forEach(function (s) {
            s.classList.toggle('is-active', parseInt(s.getAttribute('data-step'), 10) === n);
        });
        document.querySelectorAll('#stepper li').forEach(function (li) {
            var num = parseInt(li.getAttribute('data-step'), 10);
            li.classList.toggle('is-active', num === n);
            li.classList.toggle('is-done', num < n);
        });
        if (n === 3) {
            updateDbGate();
            var h = document.getElementById('f-dbserver');
            var u = document.getElementById('f-dbuser');
            var b = document.getElementById('f-dbbase');
            if (h && u && b && h.value.trim() && u.value.trim() && b.value.trim() && !dbOk) {
                dbCheck();
            }
        }
        if (n === 5) { buildSummary(); }
        var active = stepEl(n);
        if (active && active.scrollIntoView) {
            active.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }

    function gateBlocked(step) { return step && step.hasAttribute('data-gate-block'); }

    function canProceed() {
        if (gateBlocked(stepEl(current))) { return false; }
        if (current === 1) {
            var agree = document.getElementById('license_agree');
            if (agree && !agree.checked) { return false; }
        }
        if (current === 3 && !dbOk) {
            // проверка идёт автоматически; если «Далее» уже нажали — продолжим после успеха
            pendingAdvance = true;
            dbCheck();
            return false;
        }
        if (current === 4 && !validateSite()) { return false; }
        return true;
    }

    function initStepper() {
        var form = document.getElementById('wizard');
        if (!form) { return; }
        form.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-nav]');
            if (!btn) { return; }
            if (btn.getAttribute('data-nav') === 'back') {
                showStep(Math.max(current - 1, 1));
            } else {
                if (canProceed()) { showStep(Math.min(current + 1, total)); }
            }
        });
        var agree = document.getElementById('license_agree');
        if (agree) {
            agree.addEventListener('change', function () {
                var nb = nextBtn(stepEl(1));
                if (nb) { nb.disabled = !agree.checked; }
            });
            var nb = nextBtn(stepEl(1));
            if (nb) { nb.disabled = !agree.checked; }
        }
    }

    /* ------------------------------------------------------------- пароли */

    function ruleState(pass, pass2) {
        return {
            len: pass.length >= 6,
            letter: /[A-Za-zА-Яа-яЁё]/.test(pass),
            digit: /[0-9]/.test(pass),
            match: pass.length > 0 && pass === pass2
        };
    }

    function initPasswords() {
        var pass = document.getElementById('f-pass');
        var pass2 = document.getElementById('f-pass2');
        if (!pass) { return; }

        document.querySelectorAll('.input-eye').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.getAttribute('data-eye'));
                if (input) { input.type = input.type === 'password' ? 'text' : 'password'; }
            });
        });

        var meter = document.getElementById('passmeter');
        var meterText = meter ? meter.querySelector('.passmeter__text') : null;
        var rules = document.getElementById('passrules');

        function update() {
            var st = ruleState(pass.value, pass2 ? pass2.value : '');
            if (rules) {
                rules.querySelectorAll('li').forEach(function (li) {
                    li.classList.toggle('is-ok', !!st[li.getAttribute('data-rule')]);
                });
            }
            if (meter) {
                var score = 0;
                if (st.len) { score++; }
                if (st.letter && st.digit) { score++; }
                if (pass.value.length >= 10 && st.letter && st.digit) { score++; }
                if (!pass.value) { score = 0; }
                meter.setAttribute('data-level', Math.max(score, 0));
                if (meterText) { meterText.textContent = score > 0 ? (L.strength[Math.min(score, 3) - 1] || '') : ''; }
            }
            if (pass2) { setFieldError(pass2, !st.match && pass2.value.length > 0); }
        }

        pass.addEventListener('input', update);
        if (pass2) { pass2.addEventListener('input', update); }
        update();
    }

    /* ------------------------------------------------------------- валидация */

    function setFieldError(input, hasError, message) {
        var field = input.closest('.field');
        if (!field) { return; }
        field.classList.toggle('has-error', !!hasError);
        input.classList.toggle('is-invalid', !!hasError);
        var err = field.querySelector('.field__error');
        if (err) {
            if (hasError && input.getAttribute('data-error')) { err.textContent = input.getAttribute('data-error'); }
            else if (message) { err.textContent = message; }
            else if (!hasError) { err.textContent = ''; }
        }
    }

    function validateInput(input) {
        var pattern = input.getAttribute('data-pattern');
        if (!pattern) { return true; }
        var ok = new RegExp(pattern).test(input.value);
        setFieldError(input, !ok);
        return ok;
    }

    function validateSite() {
        var ok = true;
        var sitename = document.getElementById('f-sitename');
        if (sitename && !sitename.value.trim()) {
            setFieldError(sitename, true, '—');
            ok = false;
        } else if (sitename) {
            setFieldError(sitename, false);
        }
        ['f-login', 'f-prefix'].forEach(function (id) {
            var input = document.getElementById(id);
            if (input && !validateInput(input)) { ok = false; }
        });
        var pass = document.getElementById('f-pass');
        var pass2 = document.getElementById('f-pass2');
        if (pass) {
            var st = ruleState(pass.value, pass2 ? pass2.value : '');
            var strong = st.len && st.letter && st.digit;
            setFieldError(pass, pass.value.length > 0 && !strong, L.passWeak);
            if (pass.value.length > 0 && !strong) { ok = false; }
            if (pass2 && pass2.value.length > 0 && !st.match) { ok = false; }
        }
        return ok;
    }

    function initValidation() {
        ['f-login', 'f-prefix', 'f-dbbase'].forEach(function (id) {
            var input = document.getElementById(id);
            if (input) {
                input.addEventListener('blur', function () { validateInput(input); });
                input.addEventListener('input', function () { setFieldError(input, false); });
            }
        });
        var sitename = document.getElementById('f-sitename');
        if (sitename) { sitename.addEventListener('input', function () { setFieldError(sitename, false); }); }

        var form = document.getElementById('wizard');
        if (form) {
            form.addEventListener('submit', function (e) {
                var ok = validateSite();
                var dbcheck = document.getElementById('f-dbbase');
                var okDb = dbcheck ? validateInput(dbcheck) : true;
                if (!ok || !okDb) {
                    e.preventDefault();
                    showStep(!okDb ? 3 : 4);
                    return;
                }
                var btn = document.getElementById('btnInstall');
                if (btn) {
                    btn.textContent = L.installing || '...';
                    setTimeout(function () { btn.disabled = true; }, 0);
                }
            });
        }
    }

    /* ------------------------------------------------------------- проверка БД */

    var dbcheckTimer = null;
    var pendingAdvance = false;

    function updateDbGate() {
        if (dbOk && pendingAdvance) {
            pendingAdvance = false;
            showStep(4);
        }
    }

    function dbCheck() {
        var result = document.getElementById('dbcheck-result');
        var host = document.getElementById('f-dbserver');
        var user = document.getElementById('f-dbuser');
        var dbpass = document.getElementById('f-dbpass');
        var base = document.getElementById('f-dbbase');
        if (!host || !user || !base) { return; }

        var data = new URLSearchParams();
        data.set('ajax', 'dbcheck');
        data.set('db_server', host.value.trim());
        data.set('db_user', user.value.trim());
        data.set('db_password', dbpass ? dbpass.value : '');
        data.set('db_base', base.value.trim());
        data.set('db_create', '1'); // отсутствующая база создаётся автоматически

        if (result) {
            result.className = 'dbcheck__result is-checking';
            result.textContent = L.dbChecking || '...';
        }

        fetch('/install/', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: data.toString()
        }).then(function (r) { return r.json(); }).then(function (json) {
            dbOk = json.status === 'ok' || json.status === 'created';
            if (result) {
                result.className = 'dbcheck__result is-' + json.status;
                result.textContent = json.message || '';
            }
            updateDbGate();
        }).catch(function () {
            dbOk = false;
            if (result) { result.className = 'dbcheck__result is-error'; result.textContent = 'Network error'; }
            updateDbGate();
        });
    }

    function initDbCheck() {
        var fields = ['f-dbserver', 'f-dbuser', 'f-dbpass', 'f-dbbase'];
        var present = fields.every(function (id) { return document.getElementById(id); });
        if (!present) { return; }

        // соединение проверяется автоматически; отсутствующая база создаётся сразу
        ['f-dbserver', 'f-dbuser', 'f-dbpass', 'f-dbbase'].forEach(function (id) {
            document.getElementById(id).addEventListener('input', function () {
                dbOk = false;
                updateDbGate();
                clearTimeout(dbcheckTimer);
                dbcheckTimer = setTimeout(function () {
                    var host = document.getElementById('f-dbserver').value.trim();
                    var user = document.getElementById('f-dbuser').value.trim();
                    var base = document.getElementById('f-dbbase').value.trim();
                    if (host && user && base) { dbCheck(); }
                }, 700);
            });
        });
    }

    /* ------------------------------------------------------------- сводка */

    function esc(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text || ''));
        return div.innerHTML;
    }

    function buildSummary() {
        var box = document.getElementById('summary');
        if (!box) { return; }
        var val = function (id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; };
        var demo = document.querySelector('input[name="demodata"]:checked');
        var rows = [
            [L.summarySite, val('f-sitename')],
            [L.summaryAdmin, val('f-login')],
            [L.summaryDb, val('f-dbuser') + '@' + val('f-dbserver') + ' / ' + val('f-dbbase')],
            [L.summaryPrefix, val('f-prefix')],
            [L.summaryDemo, demo && demo.value === '1' ? L.summaryDemoYes : L.summaryDemoNo]
        ];
        box.innerHTML = rows.map(function (r) {
            return '<div class="summary__row"><span class="summary__k">' + esc(r[0]) + '</span>' +
                   '<span class="summary__v">' + esc(r[1]) + '</span></div>';
        }).join('');
    }

    /* ------------------------------------------------------------- init */

    window.INSTALL = window.INSTALL || {};

    window.INSTALL.init = function () {
        initTheme();
        initLang();
        initStepper();
        initPasswords();
        initValidation();
        initDbCheck();
        showStep(1);
    };

})();
