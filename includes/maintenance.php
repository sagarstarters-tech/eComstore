<?php
/**
 * Maintenance Mode Guard
 * Location: /includes/maintenance.php
 *
 * Include this ONCE at the top of any page that should
 * be hidden from visitors during maintenance mode.
 * Admins (session role = admin) bypass the maintenance screen.
 *
 * Usage:
 *   require_once 'includes/maintenance.php';
 *   checkMaintenanceMode();
 */

function checkMaintenanceMode(): void {
    global $global_settings;

    // Read setting
    $maintenance = isset($global_settings['maintenance_mode']) && $global_settings['maintenance_mode'] == '1';

    if (!$maintenance) return;
    
    // Exempt login pages so admins can log in
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page === 'admin_login.php' || $current_page === 'login.php' || $current_page === 'admin_auth.php' || $current_page === 'auth.php') return;

    // Admin bypass
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') return;

    // Show maintenance page and stop execution
    http_response_code(503);
    $site_url = defined('SITE_URL') ? SITE_URL : '';
    $assets_url = defined('ASSETS_URL') ? ASSETS_URL : '/assets';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Under Maintenance — Sagar Starter's</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
    <style>
        body { background: linear-gradient(135deg, #007aff 0%, #005ecc 100%); display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Segoe UI', sans-serif; margin: 0; }
        .box { background: #fff; border-radius: 20px; text-align: center; padding: 60px 50px; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
        .icon { font-size: 4rem; color: #007aff; margin-bottom: 20px; }
        h1 { font-size: 2rem; font-weight: 800; color: #222; margin-bottom: 12px; }
        p { color: #666; line-height: 1.7; margin-bottom: 0; }
        .badge { background: #fff3cd; color: #856404; padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; display: inline-block; margin-bottom: 24px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon"><i class="fas fa-tools"></i></div>
        <div class="badge"><i class="fas fa-clock me-1"></i>Back Soon</div>
        <h1>We'll Be Right Back</h1>
        <p>Our store is currently down for scheduled maintenance. We're working hard to improve your experience. Please check back in a little while!</p>
    </div>
</body>
</html>
    <?php
    exit;
}
