<?php
// ⛔ DEBUG FILE — Localhost/CLI access only. Blocked by .htaccess on production.
if (php_sapi_name() !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(403); exit('Access denied.'); }
require_once 'includes/db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS custom_scripts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    header_code TEXT NULL,
    footer_code TEXT NULL,
    google_verification VARCHAR(255) NULL,
    bing_verification VARCHAR(255) NULL,
    custom_verification TEXT NULL,
    txt_instructions TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    // Insert a default empty row if none exists
    $check = $conn->query("SELECT id FROM custom_scripts LIMIT 1");
    if ($check && $check->num_rows === 0) {
        $conn->query("INSERT INTO custom_scripts (id) VALUES (1)");
    }
    echo "Table custom_scripts created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}
