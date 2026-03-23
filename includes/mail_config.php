<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', BASE_PATH . '/config/');
}
require_once CONFIG_PATH . 'config.php';
// All SMTP constants (SMTP_HOST, SMTP_USER, SMTP_PASS, SMTP_PORT, SMTP_SECURE, MAIL_FROM_NAME)
// are automatically defined in config.php.
?>
