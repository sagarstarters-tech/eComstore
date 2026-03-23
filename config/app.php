<?php
/**
 * ============================================================
 *  APPLICATION CONFIGURATION
 *  Location: /config/app.php
 * ============================================================
 *  Non-secret application settings. URL values are driven by
 *  SITE_URL in .env so the same code runs on XAMPP and Hostinger.
 * ============================================================
 */

if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

// SITE_URL = the subfolder prefix, e.g. "/store" on XAMPP or "" on Hostinger root
$_site_url = rtrim(_env('SITE_URL', ''), '/');

return [
    // ── URLs ──────────────────────────────────────────────
    'site_url'       => $_site_url,
    'assets_url'     => $_site_url . '/assets',
    'store_base_url' => $_site_url . '/',
    'admin_base_url' => $_site_url . '/admin/',

    // ── SMTP ──────────────────────────────────────────────
    'smtp_host'      => _env('SMTP_HOST', 'smtp.gmail.com'),
    'smtp_user'      => _env('SMTP_USER', 'sagarstarters@gmail.com'),
    'smtp_pass'      => _env('SMTP_PASS', 'wbgi uyxd bnsk kaqm'),
    'smtp_port'      => (int) _env('SMTP_PORT', '465'),
    'smtp_secure'    => _env('SMTP_SECURE', 'ssl'),
    'mail_from_name' => _env('MAIL_FROM_NAME', "Sagar Starter's Support"),
];
