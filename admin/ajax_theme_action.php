<?php
/**
 * ajax_theme_action.php
 * Handles Export / Import / Cache-clear for the Theme Customizer.
 * Expects: POST with action=export|import|clear_cache
 */
ob_start();
include_once __DIR__ . '/../includes/session_setup.php';
include_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/core/AuthMiddleware.php';
AuthMiddleware::check($conn);
require_once __DIR__ . '/../includes/ThemeService.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Export ────────────────────────────────────────────────────
if ($action === 'export') {
    $theme = ThemeService::getTheme($conn);
    $json  = json_encode($theme, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    ob_clean();
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="theme_export_' . date('Ymd_His') . '.json"');
    header('Content-Length: ' . strlen($json));
    echo $json;
    exit;
}

// ── Import ────────────────────────────────────────────────────
if ($action === 'import') {
    if (!isset($_FILES['theme_file']) || $_FILES['theme_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No valid file uploaded.']);
        exit;
    }

    $raw = file_get_contents($_FILES['theme_file']['tmp_name']);
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON file.']);
        exit;
    }

    // Filter to only known theme keys
    $allowed = array_keys(ThemeService::getDefaults());
    $filtered = array_intersect_key($data, array_flip($allowed));

    if (empty($filtered)) {
        echo json_encode(['success' => false, 'message' => 'No valid theme keys found in file.']);
        exit;
    }

    ThemeService::saveTheme($conn, $filtered);
    echo json_encode(['success' => true, 'message' => 'Theme imported successfully! Reload the page to apply.']);
    exit;
}

// ── Cache Clear ───────────────────────────────────────────────
if ($action === 'clear_cache') {
    $cleared = false;
    if (function_exists('opcache_reset')) {
        opcache_reset();
        $cleared = true;
    }
    // Also clear any output buffers and apc if available
    if (function_exists('apc_clear_cache')) {
        apc_clear_cache();
        $cleared = true;
    }
    echo json_encode([
        'success' => true,
        'message' => $cleared
            ? 'OPcache & APC cache cleared successfully.'
            : 'Cache cleared. (OPcache not enabled on this server)',
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
