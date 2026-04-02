<?php
require 'c:\xampp\htdocs\eComstore\includes\db_connect.php';
$res = $conn->query("SELECT * FROM whatsapp_logs ORDER BY sent_at DESC LIMIT 5");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Status: " . $row['status'] . " | Mode: " . $row['sending_mode'] . " | Date: " . $row['sent_at'] . "\n";
}
?>
