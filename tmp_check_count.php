<?php
require 'c:\xampp\htdocs\eComstore\includes\db_connect.php';
$res = $conn->query("SELECT COUNT(*) as total FROM whatsapp_logs");
$row = $res->fetch_assoc();
echo "Total logs: " . $row['total'];
?>
