<?php
/**
 * One-time Invoice Migration Runner
 * Access via browser: /admin/run_invoice_migration.php
 * DELETE THIS FILE after running successfully.
 */
include_once __DIR__ . '/../includes/session_setup.php';
include_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/core/AuthMiddleware.php';
AuthMiddleware::check($conn);

echo "<h2>Invoice Migration Runner</h2><pre>";

// 1. Create invoices table
$sql1 = "CREATE TABLE IF NOT EXISTS `invoices` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `invoice_number` varchar(50) NOT NULL,
    `order_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `invoice_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
    `shipping_total` decimal(10,2) NOT NULL DEFAULT 0.00,
    `cod_charges` decimal(10,2) NOT NULL DEFAULT 0.00,
    `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
    `payment_method` varchar(50) DEFAULT NULL,
    `access_token` varchar(64) NOT NULL,
    `status` enum('generated','sent','viewed') DEFAULT 'generated',
    `whatsapp_sent` tinyint(1) NOT NULL DEFAULT 0,
    `whatsapp_sent_at` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_invoice_number` (`invoice_number`),
    UNIQUE KEY `uk_invoice_order` (`order_id`),
    KEY `idx_access_token` (`access_token`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql1)) {
    echo "✅ invoices table created successfully.\n";
} else {
    echo "❌ Error creating invoices table: " . $conn->error . "\n";
}

// 2. Insert default invoice settings
$settings = [
    ['invoice_auto_generate', '1'],
    ['invoice_auto_send_whatsapp', '0'],
    ['invoice_prefix', 'INV'],
    ['invoice_store_name', "Sagar Starter's"],
    ['invoice_store_address', ''],
    ['invoice_store_phone', ''],
    ['invoice_store_email', ''],
    ['invoice_gst_number', ''],
    ['invoice_footer_text', 'Thank you for shopping with us!'],
    ['invoice_terms', 'Goods once sold are not returnable. Subject to local jurisdiction.'],
];

$stmt = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
$count = 0;
foreach ($settings as $s) {
    $stmt->bind_param("ss", $s[0], $s[1]);
    if ($stmt->execute() && $stmt->affected_rows > 0) $count++;
}
$stmt->close();
echo "✅ $count invoice settings inserted.\n";

echo "\n<strong>Migration complete! Now delete this file from the server.</strong>";
echo "</pre>";
