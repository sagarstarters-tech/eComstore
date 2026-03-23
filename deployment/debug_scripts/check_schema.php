<?php
// ⛔ DEBUG FILE — Localhost/CLI access only. Blocked by .htaccess on production.
if (php_sapi_name() !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(403); exit('Access denied.'); }
require_once 'includes/db_connect.php';

$tables = ['email_logs', 'whatsapp_logs'];
foreach ($tables as $table) {
    echo "Table: $table\n";
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "  {$row['Field']} - {$row['Type']}\n";
        }
    } else {
        echo "  Error: " . $conn->error . "\n";
    }
}
