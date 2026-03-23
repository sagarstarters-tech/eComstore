<?php
$conn = new mysqli('localhost', 'root', '', 'jevarmart_db');
if ($conn->connect_error) {
    die('Conn Error');
}
$res = $conn->query("SHOW TABLES LIKE 'orders'");
if ($res->num_rows > 0) {
    echo "Orders in jevarmart_db: " . $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'] . "\n";
}
