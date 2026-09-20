/**
 * InstantCMS installer — vanilla JS (2026 redesign, style guide: banki.ru).
 * Степпер, валидация, индикатор пароля, проверка соединения с БД, темы.
 */
(function () {
    'use strict';

    var L = (window.INSTALL && window.INSTALL.langJS) || {};

    /* ------------------------------------------------------------- тема */

    function initTheme() {
        var saved = null;
        try { saved = localStorage.getItem('inst_theme'); } catch (e) {}
        if (saved === 'dark') { document.body.setAttribute('data-theme', 'dark'); }
        var btn = document.getElementById('theme-toggle');
        if (btn) {
            btn.addEventListener('click', function () {
                var dark = document.body.getAttribute('data-theme') === 'dark';
                if (dark) {
                    document.body.removeAttribute('data-theme');
                } else {
                    document.body.setAttribute('data-theme', 'dark');
                }
                try { localStorage.setItem('inst_theme', dark ? 'light' : 'dark'); } catch (e) {}
            });
        }
    }

    /* ------------------------------------------------------------- язык */

    function initLang() {
        var btn = document.getElementById('langs-btn');
        var list = document.getElementById('langs-list');
        if (!btn || !list) { return; }
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            list.hidden = !list.hidden;
        });
        document.addEventListener('click', function () { list.hidden = true; });
        list.addEventListener('click', function (e) {
            var li = e.target.closest('li');
            if (!li || li.classList.contains('is-current')) { return; }
            var input = document.getElementById('langform-input');
            if (input) {
                input.value = li.getAttribute('data-lang');
                document.getElementById('langform').submit();
            }
        });
    }

    /* ------------------------------------------------------------- степпер */

    var current = 1;
    var total = 4;

    function steps() { return document.querySelectorAll('.step'); }

    function showStep(n) {
        current = n;
        steps().forEach(function (s) {
            s.classList.toggle('is-active', parseInt(s.getAttribute('data-step'), 10) === n);
        });
        document.querySelectorAll('#stepper li').forEach(function (li) {
            var num = parseInt(li.getAttribute('data-step'), 10);
            li.classList.toggle('is-active', num === n);
            li.classList.toggle('is-done', num < n);
        });
        var back = document.getElementById('btnBack');
        var next = document.getElementById('btnNext');
        var install = document.getElementById('btnInstall');
        if (back) { back.hidden = (n === 1); }
        if (next) { next.hidden = (n === total); }
        if (install) { install.hidden = (n !== total); }
        if (n === total) { validateForm(); }
        var card = document.querySelector('.step.is-active');
        if (card && card.scrollIntoView) { card.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); }
    }

    function gateBlocked(step) {
        return step.hasAttribute('data-gate-block');
    }

    function initStepper() {
        var next = document.getElementById('btnNext');
        var back = document.getElementById('btnBack');
        if (!next) { return; }
        next.addEventListener('click', function () {
            var step = document.querySelector('.step[data-step="' + current + '"]');
            if (gateBlocked(step)) { return; }
            if (step && step.getAttribute('data-step') === '1') {
                var agree = document.getElementById('license_agree');
                if (agree && !agree.checked) { return; }
            }
            showStep(Math.min(current + 1, total));
        });
        if (back) {
            back.addEventListener('click', function () {
                showStep(Math.max(current - 1, 1));
            });
        }
        var agree = document.getElementById('license_agree');
        if (agree) {
            agree.addEventListener('change', function () {
                next.disabled = !agree.checked;
            });
            next.disabled = !agree.checked;
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

        // глазки
        document.querySelectorAll('.input-eye').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.getAttribute('data-eye'));
                if (!input) { return; }
                input.type = input.type === 'password' ? 'text' : 'password';
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
                if (meterText) {
                    meterText.textContent = score > 0 ? (L.strength[Math.min(score, 3) - 1] || '') : '';
                }
            }
            if (pass2) {
                setFieldError(pass2, !st.match && pass2.value.length > 0);
            }
        }

        pass.addEventListener('input', update);
        if (pass2) { pass2.addEventListener('input', update); }
        update();
    }

    /* ------------------------------------------------------------- валидация формы */

    function setFieldError(input, hasError, message) {
        var field = input.closest('.field');
        if (!field) { return; }
        field.classList.toggle('has-error', !!hasError);
        input.classList.toggle('is-invalid', !!hasError);
        var err = field.querySelector('.field__error');
        if (err && message !== undefined) { err.textContent = hasError ? message : ''; }
        if (err && hasError && input.getAttribute('data-error')) { err.textContent = input.getAttribute('data-error'); }
    }

    function validateInput(input) {
        var pattern = input.getAttribute('data-pattern');
        if (!pattern) { return true; }
        var re = new RegExp(pattern);
        var ok = re.test(input.value);
        setFieldError(input, !ok);
        return ok;
    }

    function validateForm() {
        var ok = true;
        var pass = document.getElementById('f-pass');
        var pass2 = document.getElementById('f-pass2');
        ['f-login', 'f-dbbase', 'f-prefix'].forEach(function (id) {
            var input = document.getElementById(id);
            if (input && !validateInput(input)) { ok = false; }
        });
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
        ['f-login', 'f-dbbase', 'f-prefix'].forEach(function (id) {
            var input = document.getElementById(id);
            if (input) {
                input.addEventListener('blur', function () { validateInput(input); });
                input.addEventListener('input', function () { setFieldError(input, false); });
            }
        });
        var form = document.getElementById('wizard');
        if (form) {
            form.addEventListener('submit', function (e) {
                if (!validateForm()) {
                    e.preventDefault();
                    showStep(4);
                    return;
                }
                var btn = document.getElementById('btnInstall');
                if (btn) {
                    btn.disabled = true;
                    btn.textContent = L.installing || '...';
                }
            });
        }
    }

    /* ------------------------------------------------------------- проверка БД */

    var dbcheckTimer = null;

    function dbCheck(auto) {
        var result = document.getElementById('dbcheck-result');
        var btn = document.getElementById('btn-dbcheck');
        var host = document.getElementById('f-dbserver');
        var user = document.getElementById('f-dbuser');
        var dbpass = document.getElementById('f-dbpass');
        var base = document.getElementById('f-dbbase');
        var create = document.getElementById('f-dbcreate');
        if (!host || !user || !base) { return; }

        var data = new URLSearchParams();
        data.set('ajax', 'dbcheck');
        data.set('db_server', host.value.trim());
        data.set('db_user', user.value.trim());
        data.set('db_password', dbpass ? dbpass.value : '');
        data.set('db_base', base.value.trim());
        data.set('db_create', create && create.checked ? '1' : '0');

        if (result) {
            result.className = 'dbcheck__result is-checking';
            result.textContent = L.dbChecking || '...';
        }
        if (btn) { btn.disabled = true; }

        fetch('/install/', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: data.toString()
        }).then(function (r) { return r.json(); }).then(function (json) {
            if (result) {
                var status = json.status === 'ok' ? 'ok' : json.status;
                result.className = 'dbcheck__result is-' + status;
                result.textContent = json.message || '';
            }
        }).catch(function () {
            if (result) {
                result.className = 'dbcheck__result is-error';
                result.textContent = 'Network error';
            }
        }).finally(function () {
            if (btn) { btn.disabled = false; }
        });
    }

    function initDbCheck() {
        var btn = document.getElementById('btn-dbcheck');
        var fields = ['f-dbserver', 'f-dbuser', 'f-dbpass', 'f-dbbase', 'f-dbcreate'];
        var present = fields.every(function (id) { return document.getElementById(id); });
        if (!btn || !present) { return; }

        btn.addEventListener('click', function () { dbCheck(false); });

        var create = document.getElementById('f-dbcreate');
        ['f-dbserver', 'f-dbuser', 'f-dbpass', 'f-dbbase'].forEach(function (id) {
            document.getElementById(id).addEventListener('input', function () {
                clearTimeout(dbcheckTimer);
                dbcheckTimer = setTimeout(function () {
                    var host = document.getElementById('f-dbserver').value.trim();
                    var user = document.getElementById('f-dbuser').value.trim();
                    var base = document.getElementById('f-dbbase').value.trim();
                    if (host && user && base) { dbCheck(true); }
                }, 700);
            });
        });
        if (create) {
            create.addEventListener('change', function () {
                var base = document.getElementById('f-dbbase').value.trim();
                if (base) { dbCheck(true); }
            });
        }
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
