<?php
require 'c:/xampp/htdocs/eComstore/includes/db_connect.php';
$res = $conn->query("DESCRIBE users");
file_put_contents('schema.json', json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT));
$res = $conn->query("DESCRIBE orders");
file_put_contents('schema2.json', json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT));
