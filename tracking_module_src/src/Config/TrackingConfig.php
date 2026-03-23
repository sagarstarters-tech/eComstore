<?php
namespace TrackingModule\Config;

use PDO;
use PDOException;

class TrackingConfig {
    public $conn;

    public function getConnection() {
        $this->conn = null;

        if (!defined('DB_HOST')) {
            $base_path = dirname(__DIR__, 3); // /tracking_module_src/src/Config -> /tracking_module_src/src -> /tracking_module_src -> /store
            require_once $base_path . '/config/config.php';
        }

        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            error_log("Tracking Database Connection Error: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
?>
