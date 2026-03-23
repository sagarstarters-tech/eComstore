<?php
require 'config/database.php';
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Tables:\n" . print_r($tables, true) . "\n";

if (in_array('users', $tables)) {
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    echo "Users columns:\n" . print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true) . "\n";
}
if (in_array('settings', $tables)) {
    $stmt = $pdo->query("SHOW COLUMNS FROM settings");
    echo "Settings columns:\n" . print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true) . "\n";
}
@unlink(__FILE__);
