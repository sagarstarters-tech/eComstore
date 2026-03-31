<?php
require_once 'includes/db_connect.php';

echo "<h2>Starting Database Migration for Persistent User Cart...</h2>";

$sql = "ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `cart_data` TEXT DEFAULT NULL AFTER `password`;";

if ($conn->query($sql)) {
    echo "<h3 style='color:green;'>Migration completed successfully!</h3>";
    echo "<p>The 'cart_data' column has been created. The checkout session empty cart bug is now fixed across devices.</p>";
    echo "<p style='color:red; font-weight:bold;'>IMPORTANT: Please delete this 'cart_migration.php' file for security reasons now.</p>";
} else {
    echo "<h3 style='color:red;'>Migration encountered an error:</h3>";
    echo "<p>" . htmlspecialchars($conn->error) . "</p>";
}
?>
