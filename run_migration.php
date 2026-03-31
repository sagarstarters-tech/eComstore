<?php
require_once 'includes/db_connect.php';

echo "<h2>Starting Database Migration for Partial COD...</h2>";

$queries = [
    "ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `advance_amount` DECIMAL(10,2) DEFAULT '0.00' AFTER `total_amount`;",
    "ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `remaining_amount` DECIMAL(10,2) DEFAULT '0.00' AFTER `advance_amount`;",
    "ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `payment_mode` VARCHAR(30) DEFAULT NULL COMMENT 'COD_PARTIAL | NULL (regular)' AFTER `remaining_amount`;",
    "INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `setting_type`) 
     SELECT * FROM (SELECT 'cod_advance_enabled', '0', 'Enable Partial COD via PhonePe', 'payment') AS tmp
     WHERE NOT EXISTS (SELECT `setting_key` FROM `settings` WHERE `setting_key` = 'cod_advance_enabled') LIMIT 1;",
    "INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `setting_type`) 
     SELECT * FROM (SELECT 'cod_advance_percentage', '30', 'Percentage for COD advance', 'payment') AS tmp
     WHERE NOT EXISTS (SELECT `setting_key` FROM `settings` WHERE `setting_key` = 'cod_advance_percentage') LIMIT 1;",
    "INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `setting_type`) 
     SELECT * FROM (SELECT 'cod_advance_min_order', '0', 'Min order amount for Partial COD', 'payment') AS tmp
     WHERE NOT EXISTS (SELECT `setting_key` FROM `settings` WHERE `setting_key` = 'cod_advance_min_order') LIMIT 1;"
];

$success = true;

foreach ($queries as $i => $sql) {
    echo "Running query " . ($i+1) . "...<br>";
    if ($conn->query($sql)) {
        echo "<span style='color:green;'>Success</span><br><hr>";
    } else {
        echo "<span style='color:red;'>Error: " . $conn->error . "</span><br><hr>";
        $success = false;
    }
}

if ($success) {
    echo "<h3>Migration completed successfully! The missing columns have been created.</h3>";
    echo "<p>You can now test the Partial COD functionality on your checkout page.</p>";
    echo "<p style='color:red; font-weight:bold;'>IMPORTANT: Please delete this 'run_migration.php' file for security reasons now.</p>";
} else {
    echo "<h3>Migration encountered errors. Please check the messages above.</h3>";
}
?>
