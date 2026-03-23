<?php
// ⛔ DEBUG FILE — Localhost/CLI access only. Blocked by .htaccess on production.
if (php_sapi_name() !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(403); exit('Access denied.'); }
include 'includes/db_connect.php';
$res = $conn->query('SELECT status FROM orders WHERE id = 1');
print_r($res->fetch_assoc());
?>
