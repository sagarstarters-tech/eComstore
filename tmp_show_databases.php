<?php
$conn = new mysqli('localhost', 'root', '');
$res = $conn->query("SHOW DATABASES");
while($row = $res->fetch_assoc()) {
    echo $row['Database'] . "\n";
}
