<?php
require_once 'includes/db_connect.php';
$res = $conn->query("SELECT * FROM phonepe_transactions ORDER BY id DESC LIMIT 5");
error_reporting(E_ALL);
ini_set('display_errors', 1);
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Order ID: " . $row['order_id'] . "\n";
    echo "Transaction ID: " . $row['transaction_id'] . "\n";
    echo "Status: " . $row['status'] . "\n";
    echo "Raw Payload: " . $row['raw_payload'] . "\n";
    echo "--------------------------\n";
}
