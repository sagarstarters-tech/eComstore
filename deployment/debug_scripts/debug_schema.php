<?php
// ⛔ DEBUG FILE — Localhost/CLI access only. Blocked by .htaccess on production.
if (php_sapi_name() !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(403); exit('Access denied.'); }
include 'includes/db_connect.php';

echo "--- DESCRIBE order_tracking ---\n";
$res = $conn->query("DESCRIBE order_tracking");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "--- DESCRIBE orders ---\n";
$res = $conn->query("DESCRIBE orders");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
