<?php
// ⛔ DEBUG FILE — Localhost/CLI access only. Blocked by .htaccess on production.
if (php_sapi_name() !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(403); exit('Access denied.'); }
require_once 'tracking_module_src/src/Config/TrackingConfig.php';

$config = new \TrackingModule\Config\TrackingConfig();
$pdo = $config->getConnection();

echo "--- Checking Order ID 3 via PDO ---\n";
$stmt = $pdo->prepare("SELECT id FROM orders WHERE id = 3");
$stmt->execute();
$order = $stmt->fetch();

if ($order) {
    echo "Order 3 exists. ID: " . $order['id'] . "\n";
} else {
    echo "Order 3 does NOT exist.\n";
    
    echo "--- Listing latest 5 orders ---\n";
    $stmt = $pdo->prepare("SELECT id FROM orders ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        echo "Order ID: " . $row['id'] . "\n";
    }
}
?>
