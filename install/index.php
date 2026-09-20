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
//              Installer UI 2026: two-column wizard (dark sidebar)           //
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
    // база создаётся автоматически, если её нет (отдельная галочка больше не нужна)
    $create = cmsCore::request('db_create', 'int', 1) ? true : false;

    if (!$host || !$user || !$base) {
        return array('status' => 'error', 'message' => $_LANG['INS_DB_HOST_EMPTY']);
    }
    if (!preg_match('/^[A-Za-z0-9_\-.\$]{1,64}$/', $base)) {
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
            try { mysqli_select_db($dbh, $base); } catch (Throwable $e) { /* проверим по $created */ }
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
    $_CFG['lang']      = $inConf->lang;
    $sql_file = PATH . '/install/' . (cmsCore::request('demodata', 'int') ? $sqldumpdemo : $sqldumpempty);

    $admin_login     = trim(cmsCore::request('admin_login', 'html', ''));
    $admin_password  = cmsCore::request('admin_password', 'html', '');
    $admin_password2 = cmsCore::request('admin_password2', 'html', '');

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

// Данные для бокового степпера
$steps = array(
    1 => array($_LANG['INS_STEP1_TITLE'], $_LANG['INS_STEP1_SUB']),
    2 => array($_LANG['INS_STEP2_TITLE'], $_LANG['INS_STEP2_SUB']),
    3 => array($_LANG['INS_STEP3_TITLE'], $_LANG['INS_STEP3_SUB']),
    4 => array($_LANG['INS_STEP4_TITLE'], $_LANG['INS_STEP4_SUB']),
    5 => array($_LANG['INS_STEP5_TITLE'], $_LANG['INS_STEP5_SUB']),
);
?>
<!DOCTYPE html>
<html lang="<?php echo $inConf->lang; ?>">
<head>
    <title><?php echo $_LANG['INS_HEADER'] . ' ' . CORE_VERSION; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/install/fonts/golos.css">
    <link type='text/css' href='/install/css/installer.css' rel='stylesheet' media='screen' />
</head>
<body>
<div class="shell<?php echo $installed ? ' shell--done' : ''; ?>">
    <?php if (!$installed) { ?>
    <aside class="side">
        <div class="side__brand">
            <span class="side__logo" aria-hidden="true">
                <svg viewBox="0 0 32 32"><path d="M18.6 2 6 18h7l-2.4 12L25 14h-7.4L18.6 2z"/></svg>
            </span>
            <span class="side__title">Instant<em>CMS</em></span>
        </div>
        <div class="side__sub"><?php echo $_LANG['INS_INSTALL_SUBTITLE']; ?></div>

        <ol class="vsteps" id="stepper">
            <?php foreach ($steps as $num => $s) { ?>
            <li data-step="<?php echo $num; ?>" class="<?php echo $num === 1 ? 'is-active' : ''; ?>">
                <span class="vsteps__dot"><?php echo $num; ?></span>
                <span class="vsteps__txt">
                    <b><?php echo $s[0]; ?></b>
                    <i><?php echo $s[1]; ?></i>
                </span>
            </li>
            <?php } ?>
        </ol>

        <div class="side__art" aria-hidden="true">
            <svg viewBox="0 0 260 200">
                <defs>
                    <linearGradient id="srv" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="#4f7cff"/><stop offset="1" stop-color="#2b4bd8"/>
                    </linearGradient>
                    <linearGradient id="cld" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#dbe6ff"/><stop offset="1" stop-color="#a8c0f0"/>
                    </linearGradient>
                </defs>
                <ellipse cx="130" cy="180" rx="86" ry="12" fill="#0e1730" opacity=".5"/>
                <g>
                    <rect x="66" y="120" width="128" height="30" rx="8" fill="url(#srv)"/>
                    <rect x="66" y="86" width="128" height="30" rx="8" fill="url(#srv)" opacity=".92"/>
                    <rect x="66" y="52" width="128" height="30" rx="8" fill="url(#srv)" opacity=".84"/>
                    <circle cx="82" cy="135" r="4" fill="#7dfba8"/>
                    <circle cx="82" cy="101" r="4" fill="#7dfba8"/>
                    <circle cx="82" cy="67" r="4" fill="#ffd166"/>
                    <rect x="96" y="132" width="70" height="5" rx="2.5" fill="#ffffff" opacity=".35"/>
                    <rect x="96" y="98" width="52" height="5" rx="2.5" fill="#ffffff" opacity=".3"/>
                    <rect x="96" y="64" width="62" height="5" rx="2.5" fill="#ffffff" opacity=".25"/>
                </g>
                <path d="M96 44a26 26 0 0 1 50-8 18 18 0 0 1 24 17H96a10 10 0 0 1 0-9z" fill="url(#cld)"/>
                <g transform="translate(206 128)">
                    <circle r="22" fill="#2f6bff"/>
                    <path d="M0-10 4-2.8l8-1.8-4.5 6.6L12 8.2 4 6.4 0 14l-4-7.6-8 1.8 4.5-6.2L-12-2.8l8 1.8z" fill="#fff" opacity=".95"/>
                </g>
                <circle cx="64" cy="30" r="10" fill="#2f6bff" opacity=".25"/>
                <circle cx="216" cy="42" r="16" fill="#2f6bff" opacity=".16"/>
                <circle cx="228" cy="86" r="7" fill="#2f6bff" opacity=".3"/>
            </svg>
        </div>

        <div class="side__foot">
            <b>InstantCMS <?php echo CORE_VERSION; ?></b>
            <span><?php echo $_LANG['INS_SIDE_FOOT']; ?></span>
        </div>
    </aside>
    <?php } ?>

    <main class="content">
        <div class="content__top">
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

        <?php if (!$installed) { ?>
        <?php $messages = cmsCore::getSessionMessages(); ?>
        <?php if ($messages) { ?>
            <div class="messages">
                <?php foreach ($messages as $message) { echo $message; } ?>
            </div>
        <?php } ?>

        <form class="wizard" id="wizard" action="/install/" method="post" novalidate>
            <!-- Шаг 1: приветствие -->
            <section class="step is-active" data-step="1">
                <div class="hero" aria-hidden="true">
                    <svg viewBox="0 0 420 240">
                        <defs>
                            <linearGradient id="win" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#2f6bff"/><stop offset="1" stop-color="#5aa2ff"/>
                            </linearGradient>
                        </defs>
                        <ellipse cx="212" cy="204" rx="150" ry="18" fill="#2f6bff" opacity=".08"/>
                        <circle cx="98" cy="84" r="46" fill="#2f6bff" opacity=".08"/>
                        <circle cx="320" cy="64" r="30" fill="#2f6bff" opacity=".12"/>
                        <g>
                            <rect x="110" y="44" width="220" height="140" rx="16" fill="#fff" stroke="url(#win)" stroke-width="3"/>
                            <rect x="110" y="44" width="220" height="30" rx="16" fill="url(#win)"/>
                            <rect x="110" y="60" width="220" height="14" fill="url(#win)"/>
                            <circle cx="128" cy="59" r="4" fill="#fff" opacity=".9"/>
                            <circle cx="142" cy="59" r="4" fill="#fff" opacity=".65"/>
                            <circle cx="156" cy="59" r="4" fill="#fff" opacity=".45"/>
                            <rect x="132" y="94" width="96" height="9" rx="4.5" fill="#c9d8f5"/>
                            <rect x="132" y="112" width="140" height="9" rx="4.5" fill="#dde7fa"/>
                            <rect x="132" y="130" width="118" height="9" rx="4.5" fill="#dde7fa"/>
                            <rect x="132" y="148" width="70" height="9" rx="4.5" fill="#e8eefc"/>
                        </g>
                        <g transform="translate(252 96)">
                            <rect x="0" y="0" width="64" height="64" rx="18" fill="#eef4ff"/>
                            <path d="M38 14 22 38h9l-3 14 18-24h-9l1-14z" fill="#2f6bff"/>
                        </g>
                        <g transform="translate(336 148)">
                            <circle r="26" fill="#2f6bff"/>
                            <path d="M0-12 4.5-3.5 13-6l-5.5 7.5L13 9 4.5 6.5 0 15l-4.5-8.5L-13 9l5.5-7.5L-13-6l8.5 2.5z" fill="#fff" opacity=".95"/>
                        </g>
                    </svg>
                </div>

                <h1><?php echo $_LANG['INS_WELCOME_H1']; ?></h1>
                <p class="lead"><?php echo $_LANG['INS_WELCOME_LEAD']; ?></p>

                <div class="panel panel--info">
                    <div class="panel__title">
                        <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 9h8M8 13h8M8 17h5"/></svg>
                        <?php echo $_LANG['INS_BEFORE_START']; ?>
                    </div>
                    <ul class="ticks">
                        <li><?php echo $_LANG['INS_TICK_SERVER']; ?></li>
                        <li><?php echo $_LANG['INS_TICK_DB']; ?></li>
                        <li><?php echo $_LANG['INS_TICK_FILES']; ?></li>
                        <li><?php echo $_LANG['INS_TICK_EXT']; ?></li>
                    </ul>
                </div>

                <label class="checkbox license">
                    <input type="checkbox" id="license_agree">
                    <span class="checkbox__box" aria-hidden="true"><svg viewBox="0 0 12 10"><path d="M1 5.5 4.2 8.5 11 1.5"/></svg></span>
                    <span><?php echo $_LANG['INS_ACCEPT_LICENSE']; ?></span>
                </label>

                <div class="actions">
                    <span></span>
                    <button type="button" class="btn btn--primary" data-nav="next">
                        <?php echo $_LANG['INS_NEXT']; ?>
                        <svg class="ico" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>
                </div>
            </section>

            <!-- Шаг 2: проверка системы -->
            <section class="step" data-step="2" <?php if (!$info['valid'] || !$includes_ok) { ?>data-gate-block="1"<?php } ?>>
                <h1><?php echo $_LANG['INS_STEP2_TITLE']; ?></h1>
                <p class="lead"><?php echo $_LANG['INS_CHECKPHP_HINT']; ?></p>
                <?php if (!$info['valid'] || !$includes_ok) { ?><div class="alert alert--error"><?php echo $_LANG['INS_REQ_FAIL']; ?></div><?php } ?>

                <div class="panel">
                    <div class="panel__title">
                        <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                        <?php echo $_LANG['INS_CHECK_PHP']; ?>
                    </div>
                    <div class="checklist">
                        <div class="checklist__row">
                            <span class="checklist__ico <?php echo $info['php']['valid'] ? 'is-ok' : 'is-fail'; ?>"><svg viewBox="0 0 24 24"><?php echo $info['php']['valid'] ? '<path d="M4.5 12.5 10 18 19.5 7"/>' : '<path d="M6 6l12 12M18 6 6 18"/>'; ?></svg></span>
                            <span class="checklist__name"><?php echo $_LANG['INS_PHP_VERSION']; ?></span>
                            <span class="badge <?php echo $info['php']['valid'] ? 'badge--ok' : 'badge--fail'; ?>"><?php echo $info['php']['version']; ?></span>
                        </div>
                        <?php foreach ($info['ext'] as $name => $valid) { ?>
                        <div class="checklist__row">
                            <span class="checklist__ico <?php echo $valid ? 'is-ok' : 'is-fail'; ?>"><svg viewBox="0 0 24 24"><?php echo $valid ? '<path d="M4.5 12.5 10 18 19.5 7"/>' : '<path d="M6 6l12 12M18 6 6 18"/>'; ?></svg></span>
                            <span class="checklist__name"><?php echo $name; ?></span>
                            <span class="badge <?php echo $valid ? 'badge--ok' : 'badge--fail'; ?>"><?php echo $valid ? $_LANG['INS_INSTALL_OK'] : $_LANG['INS_INSTALL_NOTFOUND']; ?></span>
                        </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel__title">
                        <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                        <?php echo $_LANG['INS_CHECK_FOLDER']; ?>
                    </div>
                    <div class="checklist">
                        <?php foreach ($permissions as $name => $permission) { ?>
                        <div class="checklist__row">
                            <span class="checklist__ico <?php echo $permission['valid'] ? 'is-ok' : 'is-fail'; ?>"><svg viewBox="0 0 24 24"><?php echo $permission['valid'] ? '<path d="M4.5 12.5 10 18 19.5 7"/>' : '<path d="M6 6l12 12M18 6 6 18"/>'; ?></svg></span>
                            <span class="checklist__name">/<?php echo $name; ?><?php echo $permission['perm'] ? ' <span class="text-tertiary">· ' . $_LANG['INS_PERMISSION'] . ' ' . $permission['perm'] . '</span>' : ''; ?></span>
                            <span class="badge <?php echo $permission['valid'] ? 'badge--ok' : 'badge--fail'; ?>"><?php echo $permission['valid'] ? $_LANG['INS_PERMISSION_OK'] : $_LANG['INS_PERMISSION_NO']; ?></span>
                        </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="actions">
                    <button type="button" class="btn btn--ghost" data-nav="back">
                        <svg class="ico" viewBox="0 0 24 24"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                        <?php echo $_LANG['INS_BACK']; ?>
                    </button>
                    <button type="button" class="btn btn--primary" data-nav="next">
                        <?php echo $_LANG['INS_NEXT']; ?>
                        <svg class="ico" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>
                </div>
            </section>

            <!-- Шаг 3: база данных -->
            <section class="step" data-step="3">
                <h1><?php echo $_LANG['INS_DB_STEP_TITLE']; ?></h1>
                <p class="lead"><?php echo $_LANG['INS_DB_STEP_HINT']; ?></p>

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
                        <input class="input" id="f-dbbase" name="db_base" type="text" autocomplete="off" data-pattern="^[A-Za-z0-9_\-.$]{1,64}$" data-error="<?php echo $_LANG['INS_DB_NAME_INVALID']; ?>">
                        <span class="field__error" aria-live="polite"></span>
                    </div>
                </div>

                <div class="dbcheck">
                    <span class="dbcheck__result" id="dbcheck-result" aria-live="polite"></span>
                </div>

                <div class="field">
                    <label class="field__label" for="f-prefix"><?php echo $_LANG['INS_FORM_PREFIX']; ?></label>
                    <input class="input" id="f-prefix" name="db_prefix" type="text" value="cms" data-pattern="^[A-Za-z][A-Za-z0-9_]*$" data-error="<?php echo $_LANG['INS_PREFIX_INVALID']; ?>">
                    <span class="field__error" aria-live="polite"></span>
                </div>

                <div class="actions">
                    <button type="button" class="btn btn--ghost" data-nav="back">
                        <svg class="ico" viewBox="0 0 24 24"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                        <?php echo $_LANG['INS_BACK']; ?>
                    </button>
                    <button type="button" class="btn btn--primary" data-nav="next">
                        <?php echo $_LANG['INS_NEXT']; ?>
                        <svg class="ico" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>
                </div>
            </section>

            <!-- Шаг 4: настройки сайта -->
            <section class="step" data-step="4">
                <h1><?php echo $_LANG['INS_STEP4_TITLE']; ?></h1>
                <p class="lead"><?php echo $_LANG['INS_SITE_STEP_HINT']; ?></p>

                <div class="group-title"><?php echo $_LANG['INS_GROUP_SITE']; ?></div>

                <div class="field">
                    <label class="field__label" for="f-sitename"><?php echo $_LANG['INS_FORM_SITE']; ?></label>
                    <input class="input" id="f-sitename" name="sitename" type="text" value="<?php echo $_LANG['CFG_SITENAME']; ?>">
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
                        <label class="segment__opt"><input type="radio" name="demodata" value="0" checked disabled><span><?php echo $_LANG['NO']; ?></span></label>
                        <?php } ?>
                    </div>
                    <div class="field__hint"><?php echo $_LANG['INS_DEMO_HINT']; ?></div>
                </div>

                <div class="group-title"><?php echo $_LANG['INS_GROUP_ADMIN']; ?></div>

                <div class="field field--narrow">
                    <label class="field__label" for="f-login"><?php echo $_LANG['INS_FORM_LOGIN']; ?></label>
                    <input class="input" id="f-login" name="admin_login" type="text" value="admin" autocomplete="username" data-pattern="^[A-Za-z0-9_\-]{3,}$" data-error="<?php echo $_LANG['INS_ADMIN_LOGIN_INVALID']; ?>">
                    <span class="field__error" aria-live="polite"></span>
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
                        <span class="field__error" aria-live="polite"></span>
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

                <div class="actions">
                    <button type="button" class="btn btn--ghost" data-nav="back">
                        <svg class="ico" viewBox="0 0 24 24"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                        <?php echo $_LANG['INS_BACK']; ?>
                    </button>
                    <button type="button" class="btn btn--primary" data-nav="next">
                        <?php echo $_LANG['INS_NEXT']; ?>
                        <svg class="ico" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>
                </div>
            </section>

            <!-- Шаг 5: завершение -->
            <section class="step" data-step="5">
                <h1><?php echo $_LANG['INS_FINISH_TITLE']; ?></h1>
                <p class="lead"><?php echo $_LANG['INS_FINISH_HINT']; ?></p>

                <div class="panel">
                    <div class="panel__title">
                        <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11l3 3 8-8M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9"/></svg>
                        <?php echo $_LANG['INS_SUMMARY']; ?>
                    </div>
                    <div class="summary" id="summary"></div>
                </div>

                <div class="alert alert--warning"><?php echo $_LANG['INS_DELETE_TODO']; ?></div>

                <div class="actions">
                    <button type="button" class="btn btn--ghost" data-nav="back">
                        <svg class="ico" viewBox="0 0 24 24"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                        <?php echo $_LANG['INS_BACK']; ?>
                    </button>
                    <button type="submit" class="btn btn--primary" name="install" value="1" id="btnInstall">
                        <?php echo $_LANG['INS_DO_INSTALL']; ?>
                        <svg class="ico" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>
                </div>
            </section>
        </form>
        <?php } else { ?>
        <section class="done">
            <div class="done__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4.5 12.5 10 18 19.5 7"/></svg>
            </div>
            <h1><?php echo $_LANG['INS_FORM_SUCCESS']; ?></h1>
            <p class="done__sub"><?php echo $_LANG['INS_FORM_SUCCESS_SUB']; ?></p>
            <div class="done__links">
                <a class="btn btn--primary" href="/"><?php echo $_LANG['INS_GO_SITE']; ?></a>
                <a class="btn btn--ghost" href="/admin"><?php echo $_LANG['INS_GO_CP']; ?></a>
                <a class="btn btn--ghost" target="_blank" href="http://www.instantcms.ru/wiki/doku.php"><?php echo $_LANG['INS_GO_HANDBOOK']; ?></a>
                <a class="btn btn--ghost" target="_blank" href="https://github.com/myinstantcms/icms1">GitHub</a>
            </div>
            <div class="panel panel--cron">
                <div class="panel__title"><?php echo $_LANG['INS_CRON_TODO']; ?></div>
                <p class="text"><?php echo $_LANG['INS_CRON_NOTES']; ?></p>
                <div class="cronbar">
                    <pre class="code" id="cron-cmd"><?php echo $php_path ? $php_path : 'php'; ?> -f <?php echo PATH; ?>/cron.php <?php echo $_SERVER['HTTP_HOST']; ?> > /dev/null</pre>
                    <button type="button" class="btn btn--ghost btn--copy" id="btn-copy-cron" data-copied-label="<?php echo $_LANG['INS_COPIED']; ?>">
                        <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2.5"/><path d="M5.5 15H4.5A1.5 1.5 0 0 1 3 13.5v-9A1.5 1.5 0 0 1 4.5 3h9A1.5 1.5 0 0 1 15 4.5v1"/></svg>
                        <span><?php echo $_LANG['INS_COPY']; ?></span>
                    </button>
                </div>
            </div>
        </section>
        <?php } ?>
    </main>
</div>

<form id="langform" method="post" action="/install/" hidden><input type="hidden" name="lang" id="langform-input"></form>
<script>
    window.INSTALL = {
        langJS: {
            next: <?php echo json_encode($_LANG['INS_NEXT']); ?>,
            installing: <?php echo json_encode($_LANG['INS_INSTALLING']); ?>,
            dbChecking: <?php echo json_encode($_LANG['INS_DB_CHECKING']); ?>,
            passWeak: <?php echo json_encode($_LANG['INS_PASS_WEAK']); ?>,
            strength: [<?php echo json_encode($_LANG['INS_STRENGTH_WEAK']); ?>, <?php echo json_encode($_LANG['INS_STRENGTH_MEDIUM']); ?>, <?php echo json_encode($_LANG['INS_STRENGTH_STRONG']); ?>],
            summarySite: <?php echo json_encode($_LANG['INS_SUMMARY_SITE']); ?>,
            summaryAdmin: <?php echo json_encode($_LANG['INS_SUMMARY_ADMIN']); ?>,
            summaryDb: <?php echo json_encode($_LANG['INS_SUMMARY_DB']); ?>,
            summaryPrefix: <?php echo json_encode($_LANG['INS_SUMMARY_PREFIX']); ?>,
            summaryDemo: <?php echo json_encode($_LANG['INS_SUMMARY_DEMO']); ?>,
            summaryDemoYes: <?php echo json_encode($_LANG['INS_SUMMARY_DEMO_YES']); ?>,
            summaryDemoNo: <?php echo json_encode($_LANG['INS_SUMMARY_DEMO_NO']); ?>,
            dbCreateLabel: <?php echo json_encode($_LANG['INS_DB_CREATE']); ?>
        },
        phpOk: <?php echo $info['valid'] ? 'true' : 'false'; ?>,
        includesOk: <?php echo $includes_ok ? 'true' : 'false'; ?>
    };
</script>
<script src="/install/js/install.js"></script>
<script>INSTALL.init();</script>
</body>
</html>
