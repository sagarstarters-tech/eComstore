<?php
// ⛔ DEBUG FILE — Localhost/CLI access only. Blocked by .htaccess on production.
if (php_sapi_name() !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(403); exit('Access denied.'); }
require_once 'includes/db_connect.php';
require_once 'includes/ScriptController.php';
require_once 'includes/ScriptService.php';

$controller = new ScriptController($conn);

echo "--- Testing Save ---\n";
$data = [
    'header_code' => "<!-- TEST HEADER -->\n<script>console.log('Header Test');</script>",
    'footer_code' => "<!-- TEST FOOTER -->\n<script>console.log('Footer Test');</script>",
    'google_verification' => "G-TEST-123456",
    'bing_verification' => "B-TEST-789",
    'custom_verification' => "<meta name=\"custom\" content=\"val\">",
    'txt_instructions' => "Instructions only."
];

$res = $controller->saveScripts($data);
echo "Save result: " . json_encode($res) . "\n";

echo "\n--- Testing Service/Render ---\n";
$service = new ScriptService($conn);
echo "Header Scripts:\n" . $service->getHeaderScripts() . "\n";
echo "Footer Scripts:\n" . $service->getFooterScripts() . "\n";

echo "\n--- Verify DB Persistence ---\n";
$res = $conn->query("SELECT * FROM custom_scripts WHERE id = 1");
$row = $res->fetch_assoc();
if ($row['google_verification'] === 'G-TEST-123456') {
    echo "Verification successful: Saved and retrieved matching data.\n";
} else {
    echo "Verification failed: Data mismatch.\n";
}
