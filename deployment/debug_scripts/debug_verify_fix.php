<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

// Simulate GET params
$_GET['type'] = 'home';
$_GET['id'] = 0;

// Capture output of ajax_seo_metadata.php
ob_start();
require_once 'c:/xampp/htdocs/store/admin/ajax_seo_metadata.php';
$output = ob_get_clean();

// Remove any PHP notices/warnings from the start of the output
$json_start = strpos($output, '{');
if ($json_start !== false) {
    $output = substr($output, $json_start);
}

echo "JSON Output:\n" . $output . "\n";

$data = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "Verification SUCCESS: Response is valid JSON.\n";
    if (isset($data['meta_title'])) {
        echo "Verification SUCCESS: meta_title is present.\n";
    } else {
        echo "Verification FAILED: meta_title missing.\n";
    }
} else {
    echo "Verification FAILED: Response is NOT valid JSON.\n";
}
?>
