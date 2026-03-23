<?php
define('BASE_PATH', __DIR__);
require 'includes/db_connect.php';
$res = $conn->query("SHOW COLUMNS FROM hero_slides");
while($row = $res->fetch_assoc()){
    echo $row['Field'] . "\n";
}
