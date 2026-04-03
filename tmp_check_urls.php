<?php
require 'c:\xampp\htdocs\eComstore\includes\db_connect.php';
$res = $conn->query("SELECT id, file_url FROM media_library ORDER BY id DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | URL: " . $row['file_url'] . "\n";
}
?>
