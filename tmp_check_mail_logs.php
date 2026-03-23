<?php
$conn = new mysqli('localhost', 'root', '', 'ecommerce_db');
$res = $conn->query("SELECT * FROM settings WHERE setting_key LIKE 'smtp_%'");
while($row = $res->fetch_assoc()) {
    echo $row['setting_key'] . ": " . $row['setting_value'] . "\n";
}
echo "--- Logs ---\n";
$res = $conn->query("SELECT * FROM email_logs ORDER BY id DESC LIMIT 5");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Status: " . $row['status'] . " | Error: " . $row['error_message'] . "\n";
}
