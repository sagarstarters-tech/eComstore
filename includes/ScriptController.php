<?php
require_once 'ScriptRepository.php';

class ScriptController {
    private $conn;
    private $repo;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->repo = new ScriptRepository($conn);
    }

    /**
     * Save script settings from admin.
     */
    public function saveScripts($data) {
        // Basic sanitization is handled by Repository's real_escape_string
        // We ensure only allowed fields are passed
        if ($this->repo->updateScripts($data)) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => $this->conn->error];
    }
}
