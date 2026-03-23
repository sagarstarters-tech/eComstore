<?php
// ⛔ DEBUG FILE — Localhost/CLI access only. Blocked by .htaccess on production.
if (php_sapi_name() !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(403); exit('Access denied.'); }
require_once 'tracking_module_src/src/Config/TrackingConfig.php';
require_once 'tracking_module_src/src/Repositories/TrackingRepository.php';
require_once 'tracking_module_src/src/Services/TrackingService.php';

$config = new \TrackingModule\Config\TrackingConfig();
$repo = new \TrackingModule\Repositories\TrackingRepository($config->getConnection());
$service = new \TrackingModule\Services\TrackingService($repo);

$order_id = 1; // Using order 1 which exists

// 1. Reset Order 1 to pending
echo "--- Resetting Order 1 to 'pending' ---\n";
$repo->updateOrderStatus($order_id, 'pending');
$repo->clearAllStatusHistory();

// 2. Test manage_orders.php fix (manual log)
echo "--- Testing manage_orders.php fix (status -> shipped) ---\n";
// Simulate what manage_orders.php does:
$repo->updateOrderStatus($order_id, 'shipped');
$repo->logStatusChange($order_id, 'shipped', "Status updated to Shipped via Order Management.", 'admin');

// 3. Test TrackingService enhancement
echo "--- Testing TrackingService enhancement (tracking info update) ---\n";
$service->adminUpdateTracking($order_id, 1, 'TRK-FINAL', '2026-03-15', 'shipped');

echo "--- Final history count and entries ---\n";
$final_history = $repo->getOrderStatusHistory($order_id);
echo "Count: " . count($final_history) . "\n";
foreach ($final_history as $h) {
    echo "[" . $h['created_at'] . "] " . $h['status'] . ": " . $h['notes'] . "\n";
}
?>
