<?php

class SeoRepository {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getConnection() {
        return $this->conn;
    }

    /**
     * Get all global SEO settings.
     */
    public function getGlobalSettings() {
        $settings = [];
        $res = $this->conn->query("SELECT setting_key, setting_value FROM seo_settings");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        return $settings;
    }

    /**
     * Update a global SEO setting.
     */
    public function updateGlobalSetting($key, $value) {
        $key = $this->conn->real_escape_string($key);
        $value = $this->conn->real_escape_string($value);
        return $this->conn->query("INSERT INTO seo_settings (setting_key, setting_value) VALUES ('$key', '$value') ON DUPLICATE KEY UPDATE setting_value='$value'");
    }

    /**
     * Get SEO metadata for a specific entity.
     */
    public function getMetadata($type, $id = 0) {
        $type = $this->conn->real_escape_string($type);
        $id = intval($id);
        $res = $this->conn->query("SELECT * FROM seo_metadata WHERE entity_type='$type' AND entity_id=$id");
        return ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
    }

    /**
     * Save SEO metadata for an entity.
     */
    public function saveMetadata($data) {
        $type = $this->conn->real_escape_string($data['entity_type']);
        $id = intval($data['entity_id']);
        
        $fields = [];
        foreach ($data as $key => $value) {
            if ($key === 'entity_type' || $key === 'entity_id') continue;
            $fields[] = "`$key` = '" . $this->conn->real_escape_string($value) . "'";
        }
        
        $fields_sql = implode(", ", $fields);
        
        $check = $this->conn->query("SELECT id FROM seo_metadata WHERE entity_type='$type' AND entity_id=$id");
        if ($check && $check->num_rows > 0) {
            return $this->conn->query("UPDATE seo_metadata SET $fields_sql WHERE entity_type='$type' AND entity_id=$id");
        } else {
            $cols = implode(", ", array_keys($data));
            $vals = [];
            foreach ($data as $val) $vals[] = "'" . $this->conn->real_escape_string($val) . "'";
            $vals_sql = implode(", ", $vals);
            return $this->conn->query("INSERT INTO seo_metadata ($cols) VALUES ($vals_sql)");
        }
    }
}
