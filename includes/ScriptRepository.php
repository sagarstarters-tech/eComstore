<?php

class ScriptRepository {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Get all custom scripts metadata.
     */
    public function getScripts() {
        $res = $this->conn->query("SELECT * FROM custom_scripts WHERE id = 1");
        if ($res && $res->num_rows > 0) {
            return $res->fetch_assoc();
        }
        return [
            'header_code' => '',
            'footer_code' => '',
            'google_verification' => '',
            'bing_verification' => '',
            'custom_verification' => '',
            'txt_instructions' => ''
        ];
    }

    /**
     * Update script data.
     */
    public function updateScripts($data) {
        $fields = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['header_code', 'footer_code', 'google_verification', 'bing_verification', 'custom_verification', 'txt_instructions'])) {
                $fields[] = "`$key` = '" . $this->conn->real_escape_string($value) . "'";
            }
        }
        
        if (empty($fields)) return true;
        
        $fields_sql = implode(", ", $fields);
        return $this->conn->query("UPDATE custom_scripts SET $fields_sql WHERE id = 1");
    }
}
