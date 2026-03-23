<?php
require_once __DIR__ . '/../includes/session_setup.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Check if Google Login is enabled
if (empty($global_settings['google_login_enabled']) || $global_settings['google_login_enabled'] !== '1') {
    die('Google Login is currently disabled.');
}

$client_id = $global_settings['google_client_id'] ?? '';
if (empty($client_id)) {
    die('Google Client ID is missing. Please configure it in the Admin Panel.');
}

// Generate the absolute redirect URI
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$baseUrl = defined('SITE_URL') ? SITE_URL : '';
$site_base = (strpos($baseUrl, 'http') === 0) ? rtrim($baseUrl, '/') : ($protocol . '://' . $host . rtrim($baseUrl, '/'));
$redirect_uri = $site_base . '/auth/google_callback.php';

// Generate a random hash for state to protect against CSRF
$_SESSION['google_oauth_state'] = bin2hex(random_bytes(16));

$auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'response_type' => 'code',
    'scope' => 'email profile',
    'state' => $_SESSION['google_oauth_state'],
    'access_type' => 'online',
    // 'prompt' => 'consent' // Optional: force consent screen every time
]);

header('Location: ' . $auth_url);
exit;
?>
