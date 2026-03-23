<?php

namespace ShippingModule\Config;

use PDO;
use PDOException;

/**
 * ShippingConfig 
 * Handles database connection securely using PDO for the Shipping Module.
 * Designed to be portable.
 */
class ShippingConfig {
    public $conn;

    /**
     * Get the database connection
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;

        if (!defined('DB_HOST')) {
            $base_path = dirname(__DIR__, 4); // /shipping_module_src/src/Config -> /
            require_once $base_path . '/config/config.php';
        }

        try {
            // Using UTF8MB4 for full charset support
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
                DB_USER, 
                DB_PASS
            );
            
            // Set rigorous error modes to strictly throw exceptions cleanly
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // For fetching associative arrays naturally
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch(PDOException $exception) {
            // Securely log connection errors rather than echo them out
            error_log("Shipping Module DB Connection error: " . $exception->getMessage());
            // Fail gracefully
            return null;
        }

        return $this->conn;
    }
}
?>
