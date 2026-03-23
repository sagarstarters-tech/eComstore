<?php
require_once 'includes/db_connect.php';
$conn->query("DELETE FROM phonepe_transactions WHERE transaction_id='TEST_TXN'");
if ($conn->query("INSERT INTO phonepe_transactions (order_id, transaction_id, amount, status) VALUES (1, 'TEST_TXN', 10.00, 'PENDING')")) {
    echo "INSERT SUCCESSFUL.\n";
} else {
    echo "INSERT FAILED: " . $conn->error . "\n";
}
