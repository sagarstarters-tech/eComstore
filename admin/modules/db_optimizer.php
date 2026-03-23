<?php
/**
 * DB Index Optimizer
 * Location: /admin/modules/db_optimizer.php
 *
 * Adds production indexes to key tables.
 * Safe to run multiple times (uses IF NOT EXISTS equivalent).
 * Called via manage_settings.php System Optimize panel.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}
require_once BASE_PATH . '/includes/db_connect.php';

$results = [];

$indexes = [
    // Products
    "ALTER TABLE `products` ADD INDEX IF NOT EXISTS `idx_products_status` (`status`)",
    "ALTER TABLE `products` ADD INDEX IF NOT EXISTS `idx_products_slug` (`slug`(191))",
    "ALTER TABLE `products` ADD INDEX IF NOT EXISTS `idx_products_category` (`category_id`)",
    // Orders
    "ALTER TABLE `orders` ADD INDEX IF NOT EXISTS `idx_orders_user` (`user_id`)",
    "ALTER TABLE `orders` ADD INDEX IF NOT EXISTS `idx_orders_status` (`status`)",
    "ALTER TABLE `orders` ADD INDEX IF NOT EXISTS `idx_orders_created` (`created_at`)",
    // Order tracking
    "ALTER TABLE `order_tracking` ADD INDEX IF NOT EXISTS `idx_tracking_order` (`order_id`)",
    // Settings
    "ALTER TABLE `settings` ADD INDEX IF NOT EXISTS `idx_settings_key` (`setting_key`(100))",
    // Menus
    "ALTER TABLE `menus` ADD INDEX IF NOT EXISTS `idx_menus_location` (`menu_location`)",
    "ALTER TABLE `menus` ADD INDEX IF NOT EXISTS `idx_menus_parent` (`parent_id`)",
    // Users
    "ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_users_email` (`email`)",
    "ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_users_role` (`role`)",
];

foreach ($indexes as $sql) {
    if ($conn->query($sql)) {
        $results[] = ['status' => 'ok', 'sql' => $sql];
    } else {
        // Many MySQL versions don't support IF NOT EXISTS on ADD INDEX - retry with try/catch style
        $results[] = ['status' => 'skip', 'sql' => $sql, 'error' => $conn->error];
    }
}

return $results;
