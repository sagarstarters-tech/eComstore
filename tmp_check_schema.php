<?php
require_once 'includes/db_connect.php';
$res = $conn->query("DESCRIBE phonepe_transactions");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
