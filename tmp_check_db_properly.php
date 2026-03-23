<?php
require_once 'includes/db_connect.php';
$res = $conn->query("SELECT COUNT(*) as c FROM orders");
if ($res) {
    echo "Total Orders: " . $res->fetch_assoc()['c'] . "\n";
} else {
    echo "Orders table missing or error: " . $conn->error . "\n";
}
$res = $conn->query("SELECT COUNT(*) as c FROM phonepe_transactions");
if ($res) {
    echo "Total PhonePe Txns: " . $res->fetch_assoc()['c'] . "\n";
} else {
    echo "phonepe_transactions missing or error: " . $conn->error . "\n";
}
