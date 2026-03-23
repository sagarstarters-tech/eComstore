<?php
require_once 'c:/xampp/htdocs/store/includes/db_connect.php';
$result = $conn->query("SELECT name, email, role FROM users WHERE role='admin' LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    echo "Admin Name: " . $row['name'] . "\n";
    echo "Admin Email: " . $row['email'] . "\n";
} else {
    echo "No admin user found.\n";
}
?>
