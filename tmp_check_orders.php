<?php
require_once 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " Status: " . $row['status'] . " User ID: " . $row['user_id'] . " Total: " . $row['total_amount'] . "\n";
}
