<?php
require_once 'includes/db_connect.php';
$cols = $conn->query("SHOW TABLES LIKE 'phonepe_transactions'");
if ($cols->num_rows > 0) {
    echo "Table 'phonepe_transactions' exists.\n";
    $res = $conn->query("SELECT * FROM phonepe_transactions LIMIT 5");
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Table 'phonepe_transactions' does NOT exist.\n";
}
