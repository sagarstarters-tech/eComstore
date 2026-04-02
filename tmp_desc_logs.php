<?php
require 'c:\xampp\htdocs\eComstore\includes\db_connect.php';
$res = $conn->query("DESC whatsapp_logs");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
