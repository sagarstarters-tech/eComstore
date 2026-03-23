<?php
define('BASE_PATH', __DIR__);
require 'includes/db_connect.php';
$res = $conn->query("SELECT id, title, slug FROM pages");
while($row = $res->fetch_assoc()){
    echo "ID: " . $row['id'] . " | Title: " . $row['title'] . " | Slug: [" . $row['slug'] . "]\n";
}
