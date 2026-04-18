<?php
include 'includes/db_connect.php';
$res = $conn->query("DESCRIBE product_images");
while($row = $res->fetch_assoc()){
    print_r($row);
}
?>
