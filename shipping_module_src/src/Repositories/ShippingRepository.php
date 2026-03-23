<?php

namespace ShippingModule\Repositories;

use PDO;
use Exception;

/**
 * ShippingRepository
 * Handles all direct and secure SQL queries for shipping related tasks.
 */
class ShippingRepository {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Retrieves all global shipping settings as key value pairs
     */
    public function getSettings() {
        try {
            $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM shipping_settings");
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            return $settings;
            
        } catch (Exception $e) {
            error_log("getSettings Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves all active generic shipping methods
     */
    public function getActiveShippingMethods() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY display_order ASC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("getActiveShippingMethods Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Updates a specific shipping setting securely
     */
    public function updateSetting($key, $value) {
        try {
            $stmt = $this->db->prepare("UPDATE shipping_settings SET setting_value = :value WHERE setting_key = :key");
            $stmt->bindParam(':value', $value);
            $stmt->bindParam(':key', $key);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("updateSetting Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
