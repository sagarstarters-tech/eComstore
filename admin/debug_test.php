<?php
define('BASE_PATH', dirname(__DIR__));
require_once __DIR__ . '/../includes/db_connect.php';
echo "Connected to: " . DB_NAME . "\n";
$res = $conn->query("SHOW TABLES LIKE 'testimonials'");
if ($res && $res->num_rows > 0) {
    echo "Table found!\n";
    $res = $conn->query("SELECT * FROM testimonials");
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Table NOT found!\n";
}
