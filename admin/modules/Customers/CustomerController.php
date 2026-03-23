<?php
/**
 * CustomerController
 * Wraps manage_users.php - displays customer list.
 */
require_once __DIR__ . '/../../core/BaseController.php';

class CustomerController extends BaseController
{
    public function handle(): void
    {
        $page   = max(1, intval($this->get('page', '1')));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $users  = [];
        $result = $this->conn->query(
            "SELECT * FROM users WHERE role='user' ORDER BY id DESC LIMIT $limit OFFSET $offset"
        );
        if ($result) while ($r = $result->fetch_assoc()) $users[] = $r;

        $total = (int)$this->conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];

        $this->render(__DIR__ . '/views/list.php', [
            'users'       => $users,
            'total_pages' => ceil($total / $limit),
            'page'        => $page,
            'flash'       => $this->getFlash(),
        ]);
    }
}
