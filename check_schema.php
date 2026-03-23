<?php
define('BASE_PATH', __DIR__);
require 'includes/db_connect.php';
$result = $conn->query('DESCRIBE hero_slides');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
}
