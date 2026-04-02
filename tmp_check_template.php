<?php
require 'c:\xampp\htdocs\eComstore\includes\db_connect.php';
$res = $conn->query("SELECT message_template FROM whatsapp_settings WHERE id = 1");
$row = $res->fetch_assoc();
echo $row['message_template'];
?>
