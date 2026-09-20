<?php
/**
 * PHPMailer 6.x backward-compatibility layer for InstantCMS.
 *
 * PHPMailer 5.2 reached EOL and is incompatible with PHP 8
 * (get_magic_quotes_runtime() was removed in PHP 8.0). This shim keeps
 * the legacy global class names used by InstantCMS (core/cms.php)
 * while loading the namespaced PHPMailer 6.x sources.
 *
 * The PHPMailer 6.x API is call-compatible with the way InstantCMS
 * uses it: new PHPMailer(), SetFrom(), IsSMTP(), IsSendmail() and the
 * CharSet/Host/Port/SMTPAuth/SMTPKeepAlive/Username/Password/SMTPSecure
 * properties (method names are case-insensitive in PHP, property names
 * are unchanged in 6.x).
 */

if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require_once __DIR__ . '/Exception.php';
    require_once __DIR__ . '/PHPMailer.php';
    require_once __DIR__ . '/SMTP.php';
}

class_alias(\PHPMailer\PHPMailer\PHPMailer::class, 'PHPMailer');
class_alias(\PHPMailer\PHPMailer\SMTP::class, 'SMTP');
class_alias(\PHPMailer\PHPMailer\Exception::class, 'phpmailerException');
