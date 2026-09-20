<?php
/******************************************************************************/
//                                                                            //
//                           InstantCMS v1.10.7                               //
//                        http://www.instantcms.ru/                           //
//                                                                            //
//                   written by InstantCMS Team, 2007-2016                    //
//                produced by InstantSoft, (www.instantsoft.ru)               //
//                                                                            //
//                        LICENSED BY GNU/GPL v2                              //
//                                                                            //
//         Installer UI redesign 2026 — style guide: banki.ru                 //
/******************************************************************************/

session_start();
setlocale(LC_ALL, "ru_RU.UTF-8");
header('Content-Type: text/html; charset=utf-8');

define('VALID_CMS', 1);
define('PATH', $_SERVER['DOCUMENT_ROOT']);

// Защита от повторного запуска установщика на уже установленном сайте
if (file_exists(PATH . '/includes/config.inc.php')) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/plain; charset=utf-8');
    die("InstantCMS is already installed.\nFor security reasons remove the /install and /migrate directories.\n\nInstantCMS уже установлена.\nВ целях безопасности удалите папки /install и /migrate.");
}

include(PATH . '/core/cms.php');
cmsCore::includeFile('install/function.php');
cmsCore::loadClass('config');
cmsCore::loadClass('db');
cmsCore::loadClass('user');
cmsCore::loadClass('page');
cmsCore::loadClass('actions');
$inConf = cmsConfig::getInstance();

// Мультиязычная установка
$inConf->lang = isset($_SESSION['inst_lang']) ? $_SESSION['inst_lang'] : $inConf->lang;
$langs        = cmsCore::getDirsList('/languages');
// запрос на смену языка
if (cmsCore::inRequest('lang')) {
    $inst_lang = cmsCore::request('lang', 'html', 'ru');
    if (in_array($inst_lang, $langs)) {
        $_SESSION['inst_lang'] = $inst_lang;
        $inConf->lang          = $inst_lang;
    }
}

cmsCore::loadLanguage('lang');
cmsCore::loadLanguage('install');

$installed = false;

// Можно делать мультиязычные дампы
$sqldumpdemo  = 'sqldumpdemo.sql';
$sqldumpempty = 'sqldumpempty.sql';
if ($inConf->lang != 'ru') {
    $sqldumpempty = (file_exists(PATH . '/install/sqldumpempty_' . $inConf->lang . '.sql')) ?
            'sqldumpempty_' . $inConf->lang . '.sql' : 'sqldumpempty.sql';
    $sqldumpdemo  = (file_exists(PATH . '/install/sqldumpdemo_' . $inConf->lang . '.sql')) ?
            'sqldumpdemo_' . $inConf->lang . '.sql' : $sqldumpempty;
}

////////////////// проверка соединения с БД (AJAX и установка) //////////////////

/**
 * Проверяет соединение с MySQL, при необходимости создаёт базу.
 * Никогда не приводит к фатальной ошибке: все проблемы возвращаются
 * в виде статус + сообщение.
 *
 * @return array [status, message]
 */
function install_db_check() {
    global $_LANG;

    $host   = trim(cmsCore::request('db_server', 'html', ''));
    $user   = trim(cmsCore::request('db_user', 'html', ''));
    $pass   = cmsCore::request('db_password', 'html', '');
    $base   = trim(cmsCore::request('db_base', 'html', ''));
    $create = cmsCore::request('db_create', 'int') ? true : false;

    if (!$host || !$user || !$base) {
        return array('status' => 'error', 'message' => $_LANG['INS_DB_HOST_EMPTY']);
    }
    if (!preg_match('/^[A-Za-z0-9_\-]{1,64}$/', $base)) {
        return array('status' => 'error', 'message' => $_LANG['INS_DB_NAME_INVALID']);
    }

    // PHP 8.1+: mysqli бросает исключения по умолчанию — перехватываем всё
    $dbh = false;
    try {
        $dbh = mysqli_connect($host, $user, $pass);
    } catch (Throwable $e) {
        $dbh = false;
    }
    if (!$dbh) {
        return array('status' => 'error', 'message' => $_LANG['INS_DB_SERVER_FAIL']);
    }

    $selected = false;
    $created  = false;

    try {
        $selected = (bool)mysqli_select_db($dbh, $base);
    } catch (Throwable $e) {
        $selected = false;
    }

    if (!$selected && $create) {
        try {
            $created = (bool)mysqli_query($dbh, "CREATE DATABASE `{$base}` CHARACTER SET utf8 COLLATE utf8_general_ci");
        } catch (Throwable $e) {
            $created = false;
        }
        if ($created) {
            try { mysqli_select_db($dbh, $base); } catch (Throwable $e) { /* уже проверим по $selected */ }
        }
    }

    mysqli_close($dbh);

    if ($created) {
        return array('status' => 'created', 'message' => $_LANG['INS_DB_CHECK_OK_CREATE']);
    }
    if (!$selected) {
        $status  = $create ? 'create_failed' : 'no_db';
        $message = $create ? $_LANG['INS_DB_CREATE_FAILED'] : $_LANG['INS_DB_NOT_EXISTS'];
        return array('status' => $status, 'message' => $message);
    }

    return array('status' => 'ok', 'message' => $_LANG['INS_DB_CHECK_OK']);
}

// AJAX-проверка соединения (кнопка «Проверить соединение»)
if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === 'dbcheck' && !cmsCore::inRequest('install')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(install_db_check());
    exit;
}

//////////////////// процесс установки ////////////////////////////////////////
if (cmsCore::inRequest('install')) {

    $errors = false;

    $_CFG['offtext']   = $_LANG['CFG_OFFTEXT'];
    $_CFG['keywords']  = $_LANG['CFG_KEYWORDS'];
    $_CFG['metadesc']  = $_LANG['CFG_METADESC'];

    $_CFG['sitename']  = cmsCore::request('sitename', 'html', $_LANG['CFG_SITENAME']);
    $_CFG['db_host']   = trim(cmsCore::request('db_server', 'html', ''));
    $_CFG['db_base']   = trim(cmsCore::request('db_base', 'html', ''));
    $_CFG['db_user']   = trim(cmsCore::request('db_user', 'html', ''));
    $_CFG['db_pass']   = cmsCore::request('db_password', 'html', '');
    $_CFG['db_prefix'] = trim(cmsCore::request('db_prefix', 'html', ''));
    $_CFG['lang']      = $inConf->lang; // Какой язык выбрали при установке, тот и будет сохранен в конфигурации
    $sql_file = PATH . '/install/' . (cmsCore::request('demodata', 'int') ? $sqldumpdemo : $sqldumpempty);

    $admin_login    = trim(cmsCore::request('admin_login', 'html', ''));
    $admin_password = cmsCore::request('admin_password', 'html', '');
    $admin_password2 = cmsCore::request('admin_password2', 'html', '');
    $db_create      = cmsCore::request('db_create', 'int') ? true : false;

    if (!$_CFG['db_host']) {
        cmsCore::addSessionMessage($_LANG['INS_DB_HOST_EMPTY'], 'error');
        $errors = true;
    }
    if (!$_CFG['db_base']) {
        cmsCore::addSessionMessage($_LANG['INS_DB_BASE_EMPTY'], 'error');
        $errors = true;
    }
    if (!$_CFG['db_user']) {
        cmsCore::addSessionMessage($_LANG['INS_DB_USER_EMPTY'], 'error');
        $errors = true;
    }
    if (!$_CFG['db_prefix'] || !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $_CFG['db_prefix'])) {
        cmsCore::addSessionMessage($_LANG['INS_PREFIX_INVALID'], 'error');
        $errors = true;
    }
    if (mb_strlen($admin_login) < 3 || !preg_match('/^[A-Za-z0-9_\-]+$/', $admin_login)) {
        cmsCore::addSessionMessage($_LANG['INS_ADMIN_LOGIN_INVALID'], 'error');
        $errors = true;
    }
    if (mb_strlen($admin_password) < 6 || !preg_match('/[A-Za-zА-Яа-яЁё]/', $admin_password) || !preg_match('/[0-9]/', $admin_password)) {
        cmsCore::addSessionMessage($_LANG['INS_PASS_WEAK'], 'error');
        $errors = true;
    }
    if ($admin_password !== $admin_password2) {
        cmsCore::addSessionMessage($_LANG['INS_PASS_MISMATCH'], 'error');
        $errors = true;
    }

    if ($errors) {
        cmsCore::redirect('/install/');
    }

    $inConf->db_host   = $_CFG['db_host'];
    $inConf->db_user   = $_CFG['db_user'];
    $inConf->db_pass   = $_CFG['db_pass'];
    $inConf->db_base   = $_CFG['db_base'];
    $inConf->db_prefix = $_CFG['db_prefix'];

    // Проверяем соединение и создаём базу, если её нет и отметили галочку
    $dbcheck = install_db_check();

    if ($dbcheck['status'] === 'created') {
        cmsCore::addSessionMessage($dbcheck['message'], 'success');
    } elseif ($dbcheck['status'] !== 'ok') {
        cmsCore::addSessionMessage($dbcheck['message'], 'error');
        cmsCore::redirect('/install/');
    }

    $inDB = cmsDatabase::getInstance();

    $inDB->importFromFile($sql_file);

    $d_cfg = $inConf->getDefaultConfig();
    $_CFG = array_merge($d_cfg, $_CFG);
    $inConf->saveToFile($_CFG);

    $admin_pass_hash = cmsUser::hashPassword($admin_password);
    $sql = "UPDATE cms_users SET password = '{$admin_pass_hash}', login = '{$admin_login}' WHERE id = 1";
    $inDB->query($sql);
    $sql = "UPDATE cms_users SET password = '{$admin_pass_hash}' WHERE id > 1";
    $inDB->query($sql);

    $installed = true;

    cmsCore::getInstance();
    $inUser = cmsUser::getInstance();
    $inUser->update();
    $inUser->signInUser($admin_login, $admin_password, true);

}
// =================================================================================================== //

$info        = check_requirements();
$permissions = check_permissions();
$php_path    = get_program_path('php');

// includes/ обязателен: туда пишется config.inc.php
$includes_ok = true;
foreach ($permissions as $pname => $p) {
    if ($pname === 'includes' && !$p['valid']) { $includes_ok = false; }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $inConf->lang; ?>">
<head>
    <title><?php echo $_LANG['INS_HEADER'] . ' ' . CORE_VERSION; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link type='text/css' href='/install/css/styles.css' rel='stylesheet' media='screen' />
</head>
<body>
    <header class="topbar">
        <div class="topbar__inner">
            <div class="brand">
                <div class="brand__tile" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
                <span class="brand__name">InstantCMS</span>
                <span class="brand__ver"><?php echo CORE_VERSION; ?></span>
            </div>
            <div class="topbar__actions">
                <?php if (sizeof($langs) > 1) { ?>
                <div class="langsel" id="langs">
                    <button type="button" class="langsel__btn" id="langs-btn">
                        <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.7 3.8 9S14.5 18.4 12 21c-2.5-2.6-3.8-5.7-3.8-9S9.5 5.6 12 3z"/></svg>
                        <?php echo mb_strtoupper($inConf->lang); ?>
                    </button>
                    <ul class="langsel__list" id="langs-list" hidden>
                        <?php foreach ($langs as $lng) { ?>
                        <li data-lang="<?php echo $lng; ?>" class="<?php echo $lng == $inConf->lang ? 'is-current' : ''; ?>"><?php echo mb_strtoupper($lng); ?></li>
                        <?php } ?>
                    </ul>
                </div>
                <?php } ?>
                <button type="button" class="iconbtn" id="theme-toggle" title="Theme">
                    <svg class="ico ico-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9L17 7M7 17l-2.1 2.1"/></svg>
                    <svg class="ico ico-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5z"/></svg>
                </button>
            </div>
        </div>
    </header>

    <main class="container">
        <?php if (!$installed) { ?>
        <ol class="stepper" id="stepper">
            <li class="is-active" data-step="1"><span class="stepper__dot">1</span><span class="stepper__label"><?php echo $_LANG['INS_START']; ?></span></li>
            <li data-step="2"><span class="stepper__dot">2</span><span class="stepper__label"><?php echo $_LANG['INS_CHECK_PHP_TITLE']; ?></span></li>
            <li data-step="3"><span class="stepper__dot">3</span><span class="stepper__label"><?php echo $_LANG['INS_CHECK_FOLDER_TITLE']; ?></span></li>
            <li data-step="4"><span class="stepper__dot">4</span><span class="stepper__label"><?php echo $_LANG['INS_INSTALL']; ?></span></li>
        </ol>

        <?php $messages = cmsCore::getSessionMessages(); ?>
        <?php if ($messages) { ?>
            <div class="messages">
                <?php foreach ($messages as $message) { echo $message; } ?>
            </div>
        <?php } ?>

        <form class="wizard" id="wizard" action="/install/" method="post" novalidate>
            <!-- Шаг 1: приветствие -->
            <section class="card step is-active" data-step="1">
                <h2><?php echo $_LANG['INS_WELCOME']; ?></h2>
                <div class="text"><?php echo $_LANG['INS_WELCOME_NOTES']; ?></div>
                <label class="checkbox">
                    <input type="checkbox" id="license_agree">
                    <span class="checkbox__box" aria-hidden="true"><svg viewBox="0 0 12 10"><path d="M1 5.5 4.2 8.5 11 1.5"/></svg></span>
                    <span><?php echo $_LANG['INS_ACCEPT_LICENSE']; ?></span>
                </label>
            </section>

            <!-- Шаг 2: проверка окружения -->
            <section class="card step" data-step="2" <?php if (!$info['valid']) { ?>data-gate-block="1"<?php } ?>>
                <h2><?php echo $_LANG['INS_CHECK_PHP']; ?></h2>
                <p class="text-secondary"><?php echo $_LANG['INS_CHECKPHP_HINT']; ?></p>
                <?php if (!$info['valid']) { ?><div class="alert alert--error"><?php echo $_LANG['INS_REQ_FAIL']; ?></div><?php } ?>
                <div class="checklist">
                    <div class="checklist__row">
                        <span class="checklist__name"><?php echo $_LANG['INS_PHP_VERSION']; ?></span>
                        <span class="badge <?php echo $info['php']['valid'] ? 'badge--ok' : 'badge--fail'; ?>"><?php echo $info['php']['version']; ?></span>
                    </div>
                    <?php foreach ($info['ext'] as $name => $valid) { ?>
                    <div class="checklist__row">
                        <span class="checklist__name"><?php echo $name; ?></span>
                        <span class="badge <?php echo $valid ? 'badge--ok' : 'badge--fail'; ?>"><?php echo $valid ? $_LANG['INS_INSTALL_OK'] : $_LANG['INS_INSTALL_NOTFOUND']; ?></span>
                    </div>
                    <?php } ?>
                </div>
            </section>

            <!-- Шаг 3: права на папки -->
            <section class="card step" data-step="3" <?php if (!$includes_ok) { ?>data-gate-block="1"<?php } ?>>
                <h2><?php echo $_LANG['INS_CHECK_FOLDER']; ?></h2>
                <div class="text"><?php echo $_LANG['INS_FOLDERS_NOTES']; ?></div>
                <?php if (!$includes_ok) { ?><div class="alert alert--error"><?php echo sprintf($_LANG['INS_PERMISSION_NO'], '/includes'); ?></div><?php } ?>
                <div class="checklist">
                    <?php foreach ($permissions as $name => $permission) { ?>
                    <div class="checklist__row">
                        <span class="checklist__name">/<?php echo $name; ?><?php echo $permission['perm'] ? ' <span class="text-tertiary">· ' . $_LANG['INS_PERMISSION'] . ' ' . $permission['perm'] . '</span>' : ''; ?></span>
                        <span class="badge <?php echo $permission['valid'] ? 'badge--ok' : 'badge--fail'; ?>"><?php echo $permission['valid'] ? $_LANG['INS_PERMISSION_OK'] : $_LANG['INS_PERMISSION_NO']; ?></span>
                    </div>
                    <?php } ?>
                </div>
            </section>

            <!-- Шаг 4: настройка и установка -->
            <section class="card step" data-step="4">
                <h2><?php echo $_LANG['INS_INSTALL']; ?></h2>
                <p class="text-secondary"><?php echo $_LANG['INS_FORM_INSERT']; ?></p>

                <h3 class="group-title"><?php echo $_LANG['INS_FORM_SITE']; ?></h3>
                <div class="field">
                    <label class="field__label" for="f-sitename"><?php echo $_LANG['INS_FORM_SITE']; ?></label>
                    <input class="input" id="f-sitename" name="sitename" type="text" value="<?php echo $_LANG['CFG_SITENAME']; ?>">
                </div>

                <h3 class="group-title"><?php echo $_LANG['INS_FORM_LOGIN']; ?></h3>
                <div class="grid-2">
                    <div class="field">
                        <label class="field__label" for="f-login"><?php echo $_LANG['INS_FORM_LOGIN']; ?></label>
                        <input class="input" id="f-login" name="admin_login" type="text" value="admin" autocomplete="username" data-pattern="^[A-Za-z0-9_\-]{3,}$" data-error="<?php echo $_LANG['INS_ADMIN_LOGIN_INVALID']; ?>">
                        <span class="field__error" aria-live="polite"></span>
                    </div>
                </div>
                <div class="grid-2">
                    <div class="field">
                        <label class="field__label" for="f-pass"><?php echo $_LANG['INS_FORM_PASS']; ?></label>
                        <div class="input-wrap">
                            <input class="input" id="f-pass" name="admin_password" type="password" autocomplete="new-password">
                            <button type="button" class="input-eye" data-eye="f-pass" title="<?php echo $_LANG['INS_SHOW_PASS']; ?>">
                                <svg class="ico" viewBox="0 0 24 24"><path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12z"/><circle cx="12" cy="12" r="2.8"/></svg>
                            </button>
                        </div>
                        <div class="passmeter" id="passmeter" aria-hidden="true">
                            <div class="passmeter__bar"><i></i><i></i><i></i></div>
                            <span class="passmeter__text"></span>
                        </div>
                        <ul class="passrules" id="passrules">
                            <li data-rule="len"><?php echo $_LANG['INS_PASS_RULE_LEN']; ?></li>
                            <li data-rule="letter"><?php echo $_LANG['INS_PASS_RULE_LETTER']; ?></li>
                            <li data-rule="digit"><?php echo $_LANG['INS_PASS_RULE_DIGIT']; ?></li>
                            <li data-rule="match"><?php echo $_LANG['INS_PASS_RULE_MATCH']; ?></li>
                        </ul>
                    </div>
                    <div class="field">
                        <label class="field__label" for="f-pass2"><?php echo $_LANG['INS_ADMIN_PASS_REPEAT']; ?></label>
                        <div class="input-wrap">
                            <input class="input" id="f-pass2" name="admin_password2" type="password" autocomplete="new-password">
                            <button type="button" class="input-eye" data-eye="f-pass2" title="<?php echo $_LANG['INS_SHOW_PASS']; ?>">
                                <svg class="ico" viewBox="0 0 24 24"><path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12z"/><circle cx="12" cy="12" r="2.8"/></svg>
                            </button>
                        </div>
                        <span class="field__error" aria-live="polite"></span>
                    </div>
                </div>

                <h3 class="group-title">MySQL</h3>
                <div class="grid-2">
                    <div class="field">
                        <label class="field__label" for="f-dbserver"><?php echo $_LANG['INS_FORM_MYSQL']; ?></label>
                        <input class="input" id="f-dbserver" name="db_server" type="text" value="localhost">
                    </div>
                    <div class="field">
                        <label class="field__label" for="f-dbuser"><?php echo $_LANG['INS_FORM_BDUSER']; ?></label>
                        <input class="input" id="f-dbuser" name="db_user" type="text" autocomplete="off">
                    </div>
                    <div class="field">
                        <label class="field__label" for="f-dbpass"><?php echo $_LANG['INS_BDPASS']; ?></label>
                        <div class="input-wrap">
                            <input class="input" id="f-dbpass" name="db_password" type="password" autocomplete="off">
                            <button type="button" class="input-eye" data-eye="f-dbpass" title="<?php echo $_LANG['INS_SHOW_PASS']; ?>">
                                <svg class="ico" viewBox="0 0 24 24"><path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12z"/><circle cx="12" cy="12" r="2.8"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="field">
                        <label class="field__label" for="f-dbbase"><?php echo $_LANG['INS_FORM_BDNAME']; ?></label>
                        <input class="input" id="f-dbbase" name="db_base" type="text" autocomplete="off" data-pattern="^[A-Za-z0-9_\-]{1,64}$" data-error="<?php echo $_LANG['INS_DB_NAME_INVALID']; ?>">
                        <span class="field__error" aria-live="polite"></span>
                    </div>
                </div>

                <label class="checkbox checkbox--notice">
                    <input type="checkbox" id="f-dbcreate" name="db_create" value="1">
                    <span class="checkbox__box" aria-hidden="true"><svg viewBox="0 0 12 10"><path d="M1 5.5 4.2 8.5 11 1.5"/></svg></span>
                    <span><?php echo $_LANG['INS_DB_CREATE']; ?></span>
                </label>

                <div class="dbcheck">
                    <button type="button" class="btn btn--ghost" id="btn-dbcheck"><?php echo $_LANG['INS_DB_CHECK']; ?></button>
                    <span class="dbcheck__result" id="dbcheck-result" aria-live="polite"></span>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label class="field__label" for="f-prefix"><?php echo $_LANG['INS_FORM_PREFIX']; ?></label>
                        <input class="input" id="f-prefix" name="db_prefix" type="text" value="cms" data-pattern="^[A-Za-z][A-Za-z0-9_]*$" data-error="<?php echo $_LANG['INS_PREFIX_INVALID']; ?>">
                        <span class="field__error" aria-live="polite"></span>
                    </div>
                    <div class="field">
                        <span class="field__label"><?php echo $_LANG['INS_FORM_DEMO']; ?></span>
                        <div class="segment">
                            <?php if ($sqldumpdemo != $sqldumpempty) { ?>
                            <label class="segment__opt"><input type="radio" name="demodata" value="1" checked><span><?php echo $_LANG['YES']; ?></span></label>
                            <label class="segment__opt"><input type="radio" name="demodata" value="0"><span><?php echo $_LANG['NO']; ?></span></label>
                            <?php } else { ?>
                            <label class="segment__opt"><input type="radio" name="demodata" value="1" disabled><span><?php echo $_LANG['YES']; ?></span></label>
                            <label class="segment__opt is-active"><input type="radio" name="demodata" value="0" checked disabled><span><?php echo $_LANG['NO']; ?></span></label>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="alert alert--info"><?php echo $_LANG['INS_FORM_NOTES']; ?></div>
            </section>

            <div class="wizard__controls">
                <button type="button" class="btn btn--ghost" id="btnBack" hidden><?php echo $_LANG['INS_BACK']; ?></button>
                <button type="button" class="btn btn--primary" id="btnNext"><?php echo $_LANG['INS_NEXT']; ?></button>
                <button type="submit" class="btn btn--primary" name="install" value="1" id="btnInstall" hidden><?php echo $_LANG['INS_DO_INSTALL']; ?></button>
            </div>
        </form>
        <?php } else { ?>
        <section class="card done">
            <div class="done__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4.5 12.5 10 18 19.5 7"/></svg>
            </div>
            <h2><?php echo $_LANG['INS_FORM_SUCCESS']; ?></h2>
            <div class="done__links">
                <a class="btn btn--primary" href="/"><?php echo $_LANG['INS_GO_SITE']; ?></a>
                <a class="btn btn--ghost" href="/admin"><?php echo $_LANG['INS_GO_CP']; ?></a>
                <a class="btn btn--ghost" target="_blank" href="http://www.instantcms.ru/wiki/doku.php"><?php echo $_LANG['INS_GO_HANDBOOK']; ?></a>
                <a class="btn btn--ghost" target="_blank" href="https://github.com/myinstantcms/icms1">GitHub</a>
            </div>
        </section>
        <section class="card">
            <h2><?php echo $_LANG['INS_CRON_TODO']; ?></h2>
            <p class="text"><?php echo $_LANG['INS_CRON_NOTES']; ?></p>
            <pre class="code"><?php echo $php_path ? $php_path : 'php'; ?> -f <?php echo PATH; ?>/cron.php <?php echo $_SERVER['HTTP_HOST']; ?> > /dev/null</pre>
            <h2><?php echo $_LANG['INS_ATTENTION']; ?></h2>
            <div class="alert alert--warning"><?php echo $_LANG['INS_DELETE_TODO']; ?></div>
        </section>
        <?php } ?>
    </main>

    <footer class="footer">
        <a href="http://www.instantcms.ru/" target="_blank">InstantCMS</a>, <a href="http://instantsoft.ru/" target="_blank">InstantSoft</a> &copy; 2007-<?php echo date('Y'); ?>
    </footer>

    <form id="langform" method="post" action="/install/" hidden><input type="hidden" name="lang" id="langform-input"></form>
    <script src="/install/js/install.js"></script>
    <script>
        INSTALL = {
            langJS: {
                next: <?php echo json_encode($_LANG['INS_NEXT']); ?>,
                installing: <?php echo json_encode($_LANG['INS_INSTALLING']); ?>,
                dbCheck: <?php echo json_encode($_LANG['INS_DB_CHECK']); ?>,
                dbChecking: <?php echo json_encode($_LANG['INS_DB_CHECKING']); ?>,
                passWeak: <?php echo json_encode($_LANG['INS_PASS_WEAK']); ?>,
                strength: [<?php echo json_encode($_LANG['INS_STRENGTH_WEAK']); ?>, <?php echo json_encode($_LANG['INS_STRENGTH_MEDIUM']); ?>, <?php echo json_encode($_LANG['INS_STRENGTH_STRONG']); ?>]
            },
            phpOk: <?php echo $info['valid'] ? 'true' : 'false'; ?>
        };
        INSTALL.init();
    </script>
</body>
</html>
