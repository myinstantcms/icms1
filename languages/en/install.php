<?php
/******************************************************************************/
//                                                                            //
//                           InstantCMS v1.10.6                               //
//                        http://www.instantcms.ru/                           //
//                                                                            //
//                   written by InstantCMS Team, 2007-2015                    //
//                produced by InstantSoft, (www.instantsoft.ru)               //
//                                                                            //
//                        LICENSED BY GNU/GPL v2                              //
//                                                                            //
/******************************************************************************/

$_LANG['INS_DB_HOST_EMPTY']              = 'Set database server!';
$_LANG['INS_DB_BASE_EMPTY']              = 'Set database name!';
$_LANG['INS_DB_USER_EMPTY']              = 'Set database user!';
$_LANG['INS_DB_PREFIX_EMPTY']            = 'Set database prefix!';
$_LANG['INS_ADMIN_LOGIN_EMPTY']          = 'Set administrator login, at least 3 symbols!';
$_LANG['INS_ADMIN_PASS_6']               = 'at least 6 characters!';
$_LANG['INS_ADMIN_PASS_EMPTY']           = 'Set administrator password, '.$_LANG['INS_ADMIN_PASS_6'];
$_LANG['INS_HEADER']                     = 'InstantCMS installation';
$_LANG['INS_START']                      = 'Start';
$_LANG['INS_CHECK_PHP_TITLE']            = 'Checking PHP';
$_LANG['INS_CHECK_PHP']                  = 'Checking PHP extended';
$_LANG['INS_CHECK_FOLDER_TITLE']         = 'Checking pemission';
$_LANG['INS_CHECK_FOLDER']               = 'Checking folders pemission';
$_LANG['INS_INSTALL']                    = 'Installation';
$_LANG['INS_DO_INSTALL']                 = 'Install';
$_LANG['INS_WELCOME']                    = 'Welcome';
$_LANG['INS_WELCOME_NOTES']              = '<p>Installation script will check the server for compliance with technical requirements and makes all the necessary steps to get started with InstantCMS.</p><p> InstantCMS can be installed only in the root directory of the site.</p><p> Before starting the installation create a new MySQL database on your hosting. Collation (COLLATION) must be any of utf8_* according to your needs. In most cases this is utf8_general_ci.</p><p> How to install the system on the local computer with OS Windows&trade; for testing: read <a href="http://www.instantcms.ru/wiki/doku.php/local_installation_denwer" target="_blank">instruction</a> at official site.</p><p>InstantCMS is licensed GNU/GPL version 2. You must accept the terms of the license to install the system.</p>';
$_LANG['INS_ACCEPT_LICENSE']             = ' I accept the terms <a target="_blank" href="/license.rus.txt">of the license GNU/GPL</a> (<a target="_blank" href="http://www.gnu.org/licenses/gpl-2.0.html">original in english</a>).';
$_LANG['INS_CHECKPHP_HINT'] = 'InstantCMS requires PHP 8.2 or newer with the extensions listed below. Database — MySQL 5 or newer.';
$_LANG['INS_PHP_VERSION']                = 'PHP version';
$_LANG['INS_INSTALL_VERSION']            = 'Installed version';
$_LANG['INS_NEED_EXTENTION']             = 'Required PHP extension';
$_LANG['INS_PHPNET_HINT']                = 'See description on the PHP site';
$_LANG['INS_INSTALL_OK']                 = 'Installed';
$_LANG['INS_INSTALL_NOTFOUND']           = 'Not found';
$_LANG['INS_FOLDERS_NOTES']              = '<p>For the correct working of InstantCMS folders that are pointed out below (and their included folders but with the exception of included in "/includes") must be available for recording. Change the permissions with the help of FTP-client or directly at the server using chmod.</p><p>For successful installation there must be permissions for recording at the directory "/includes". For other directories it is possible to ignore the warnings about inaccessibility of the rights for recording, but only at the time of installation.</p><p> We draw your attention to the fact that immediately after installation the directory "/includes" recording permissions should be removed for security reasons. And after the basic configuration of the site file /includes/config.inc.php must be inaccessible for the recording </p>';

$_LANG['INS_PERMISSION']                 = 'Current access permissions';
$_LANG['INS_PERMISSION_OK']              = 'writable';
$_LANG['INS_PERMISSION_NO']              = 'non writable';
$_LANG['INS_FORM_INSERT']                = 'Fill out form and click "Install" to complete the process.';
$_LANG['INS_FORM_SITE']                  = 'Website name: ';
$_LANG['INS_FORM_LOGIN']                 = 'Website Administrator Login: ';
$_LANG['INS_FORM_PASS']                  = 'Website Administrator Password: ';
$_LANG['INS_FORM_MYSQL']                 = 'MySQL server: ';
$_LANG['INS_FORM_BDNAME']                = 'Database name: ';
$_LANG['INS_FORM_BDUSER']                = 'Database user: ';
$_LANG['INS_BDPASS']                     = 'Database user password: ';
$_LANG['INS_FORM_PREFIX']                = 'Prefix tables in the database: ';
$_LANG['INS_FORM_DEMO']                  = 'Demo data: ';
$_LANG['INS_FORM_NOTES']                 = '<p>When installed with a demo data the same password will be set to all users which coincides with the administrator password. The login details of each user can be obtained from the address profile or from the Control Panel. </p><p> Installation may take from a few seconds to minutes depending on the speed of your server.</p>';
$_LANG['INS_FORM_SUCCESS']               = 'Congratulations, installation is completed!';
$_LANG['INS_FORM_SUCCESS_SUB']           = 'The system is installed and ready to be used.';
$_LANG['INS_CRON_TODO']                  = 'Create a task scheduler';
$_LANG['INS_CRON_NOTES']                 = 'Add file <strong>/cron.php</strong> in task schedule in the panel of your hosting.<br/> The interval is &mdash; 24 hours. This will allow the system to perform periodic service tasks. Possible command added to the CRON, looks like: ';
$_LANG['INS_FEEDBACK_SUPPORT']           = 'In case of difficulty, please contact Hosting technical support';
$_LANG['INS_ATTENTION']                  = 'Attention!';
$_LANG['INS_DELETE_TODO']                = 'Before proceeding you want to remove directories "install" and "migrate" from server with all files inside them!';
$_LANG['INS_GO_SITE']                    = 'Go to site';
$_LANG['INS_GO_CP']                      = 'Control Panel';
$_LANG['INS_GO_HANDBOOK']                = 'Handbook';
$_LANG['INS_GO_ADDONS']                  = 'Addons';
$_LANG['INS_NEXT']                       = 'Next';
$_LANG['INS_BACK']                       = 'Back';

$_LANG['INS_INCOMPLETE']                 = 'Installation is not completed';
$_LANG['INS_DELETE_INST_MIGRATE']        = 'If the installation process has been completed,<br/> delete folders "install" and "migrate" on the server and reload page.';
$_LANG['INS_RELOAD_PAGE']                = 'Reload page';
$_LANG['CFG_SITENAME']                   = 'My Social Network';
$_LANG['CFG_OFFTEXT']                    = 'The site is under construction';
$_LANG['CFG_KEYWORDS']                   = 'InstantCMS, management system of the site is a free CMS, site engine, CMS, social network engine';
$_LANG['CFG_METADESC']                   = 'InstantCMS is a free management system with social functions';
// Redesign 2026: DB creation, password checks, stepper
$_LANG['INS_DB_CREATE']              = 'Create the database if it does not exist';
$_LANG['INS_DB_NOT_EXISTS']          = 'The database does not exist. Tick "create the database" below or create it manually.';
$_LANG['INS_DB_CREATED']             = 'The database has been created.';
$_LANG['INS_DB_CREATE_FAILED']       = 'Could not create the database — check the MySQL user permissions.';
$_LANG['INS_DB_SERVER_FAIL']         = 'Could not connect to the MySQL server. Check the host, username and password.';
$_LANG['INS_DB_NAME_INVALID']        = 'Invalid database name: only latin letters, digits, ".", "-", "_" and "$" allowed.';
$_LANG['INS_DB_CHECK']               = 'Test connection';
$_LANG['INS_DB_CHECKING']            = 'Checking…';
$_LANG['INS_DB_CHECK_OK']            = 'Connected, the database is available.';
$_LANG['INS_DB_CHECK_OK_CREATE']     = 'Connected, the database has been created.';
$_LANG['INS_PREFIX_INVALID']         = 'Invalid prefix: latin letters and digits, starting with a letter.';
$_LANG['INS_ADMIN_LOGIN_INVALID']    = 'Login: only latin letters, digits, "-" and "_", at least 3 characters.';
$_LANG['INS_ADMIN_PASS_REPEAT']      = 'Repeat the admin password';
$_LANG['INS_PASS_MISMATCH']          = 'Passwords do not match';
$_LANG['INS_PASS_WEAK']              = 'Password: at least 6 characters, letters and digits';
$_LANG['INS_PASS_RULE_LEN']          = '6+ characters';
$_LANG['INS_PASS_RULE_LETTER']       = 'letters';
$_LANG['INS_PASS_RULE_DIGIT']        = 'digits';
$_LANG['INS_PASS_RULE_MATCH']        = 'match';
$_LANG['INS_STRENGTH_WEAK']          = 'Weak';
$_LANG['INS_STRENGTH_MEDIUM']        = 'Medium';
$_LANG['INS_STRENGTH_STRONG']        = 'Strong';
$_LANG['INS_SHOW_PASS']              = 'Show password';
$_LANG['INS_INSTALLING']             = 'Installing…';
$_LANG['INS_REQ_FAIL']               = 'Requirements are not met — installation is impossible. Configure the server and refresh the page.';
$_LANG['INS_WELCOME_LEAD']            = 'We will check the server, create the database and set up the site — it takes a couple of minutes.';
$_LANG['INS_CHIP_PHP']                = 'PHP version on the server';
$_LANG['INS_CHIP_MYSQL']              = 'database server';
$_LANG['INS_CHIP_FOLDERS']            = 'write access';
$_LANG['INS_WELCOME_DB_HINT']         = 'You do not have to create the database in advance — the installer will do it on the "Install" step if you tick the corresponding checkbox.';

// Redesign per reference: two-column wizard, 5 steps
$_LANG['INS_INSTALL_SUBTITLE']     = 'System installation';
$_LANG['INS_STEP1_TITLE']          = 'Welcome';
$_LANG['INS_STEP1_SUB']            = 'Getting started';
$_LANG['INS_STEP2_TITLE']          = 'System check';
$_LANG['INS_STEP2_SUB']            = 'Requirements and settings';
$_LANG['INS_STEP3_TITLE']          = 'Database';
$_LANG['INS_STEP3_SUB']            = 'Database connection';
$_LANG['INS_STEP4_TITLE']          = 'Site settings';
$_LANG['INS_STEP4_SUB']            = 'Basic parameters';
$_LANG['INS_STEP5_TITLE']          = 'Finish';
$_LANG['INS_STEP5_SUB']            = 'Install and sign in';
$_LANG['INS_SIDE_FOOT']            = 'A quick start for your project';
$_LANG['INS_WELCOME_H1']           = 'Welcome to the InstantCMS installation!';
$_LANG['INS_BEFORE_START']         = 'Before you start, make sure that:';
$_LANG['INS_TICK_SERVER']          = 'The web server meets the minimum requirements';
$_LANG['INS_TICK_DB']              = 'MySQL/MariaDB is available or will be created automatically';
$_LANG['INS_TICK_FILES']           = 'You have access to the site files';
$_LANG['INS_TICK_EXT']             = 'The required PHP extensions are available';
$_LANG['INS_FINISH_TITLE']         = 'Finishing the installation';
$_LANG['INS_SUMMARY']              = 'Check the parameters before starting:';
$_LANG['INS_SUMMARY_SITE']         = 'Site name';
$_LANG['INS_SUMMARY_ADMIN']        = 'Administrator';
$_LANG['INS_SUMMARY_DB']           = 'Database';
$_LANG['INS_SUMMARY_PREFIX']       = 'Table prefix';
$_LANG['INS_SUMMARY_DEMO']         = 'Data';
$_LANG['INS_SUMMARY_DEMO_YES']     = 'demo data';
$_LANG['INS_SUMMARY_DEMO_NO']      = 'clean install';
$_LANG['INS_DB_STEP_TITLE']        = 'Database connection';
$_LANG['INS_DB_STEP_HINT']         = 'Enter the MySQL server parameters — the connection is checked automatically and a missing database will be created.';
$_LANG['INS_SITE_STEP_HINT']       = 'Basic site parameters and the administrator account.';
$_LANG['INS_FINISH_HINT']          = 'We will create the tables, import the data and sign you in to the control panel.';

// Step 4 groups
$_LANG['INS_GROUP_SITE']           = 'Site';
$_LANG['INS_GROUP_ADMIN']          = 'Administrator';
$_LANG['INS_DEMO_HINT']            = 'Demo mode fills the site with sample articles, photos and posts so you can see the system in action. A clean install creates only what is required.';
$_LANG['INS_COPY']                    = 'Copy';
$_LANG['INS_COPIED']                  = 'Copied';
// Installer auto-disable
$_LANG['INS_DIRS_HINT']            = 'After installation the installer folders are renamed to _install and _migrate to protect the site from being reinstalled.';
$_LANG['INS_DIRS_RENAMED']         = 'For security the installer folders were renamed: %s. For future upgrades rename migrate back.';
$_LANG['INS_DIRS_RENAME_FAILED']   = 'Could not rename automatically: %s. Rename or delete these folders manually.';
