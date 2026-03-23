<?php
/**
 * OrderController
 * Handles order list, status updates, deletions.
 * Preserves WhatsApp modal and tracking integration.
 */
require_once __DIR__ . '/../../core/BaseController.php';

class OrderController extends BaseController
{
    public function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }
        $this->renderList();
    }

    private function handlePost(): void
    {
        $action = $this->post('action');
        switch ($action) {
            case 'update_status': $this->doUpdateStatus(); break;
            case 'delete':        $this->doDelete();       break;
            case 'clear_all':     $this->doClearAll();     break;
        }
    }

    private function doUpdateStatus(): void
    {
        $id     = intval($this->post('id'));
        $status = $this->conn->real_escape_string($this->post('status'));

        $this->conn->query("UPDATE orders SET status='$status' WHERE id=$id");

        // Log to tracking module
        require_once __DIR__ . '/../../../tracking_module_src/src/Config/TrackingConfig.php';
        require_once __DIR__ . '/../../../tracking_module_src/src/Repositories/TrackingRepository.php';
        $trackingConfig = new \TrackingModule\Config\TrackingConfig();
        $trackingRepo   = new \TrackingModule\Repositories\TrackingRepository($trackingConfig->getConnection());
        $trackingRepo->logStatusChange(
            $id, $status,
            "Status updated to " . ucwords(str_replace('_', ' ', $status)) . " via Order Management.",
            'admin'
        );

        // Send status email
        require_once __DIR__ . '/../../../includes/mail_functions.php';
        $q = $this->conn->query("SELECT u.email, u.name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $id");
        if ($q && $q->num_rows > 0) {
            $user = $q->fetch_assoc();
            sendOrderStatusEmail($this->conn, $id, $user['email'], $user['name'], $status);
        }

        $this->flash('success', "Order #$id status updated to $status.");
    }

    private function doDelete(): void
    {
        $id   = intval($this->post('id'));
        $stmt = $this->conn->prepare("DELETE FROM orders WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $this->flash('success', 'Order deleted successfully.');
    }

    private function doClearAll(): void
    {
        if ($this->conn->query("DELETE FROM orders")) {
            $this->conn->query("ALTER TABLE orders AUTO_INCREMENT = 1");
            $this->flash('success', 'All orders have been permanently deleted.');
        } else {
            $this->flash('danger', 'Failed to clear orders: ' . $this->conn->error);
        }
    }

    private function renderList(): void
    {
        $page          = max(1, intval($this->get('page', '1')));
        $limit         = 15;
        $offset        = ($page - 1) * $limit;
        $status_filter = $this->conn->real_escape_string($this->get('status', 'all'));
        $where         = ($status_filter !== 'all' && $status_filter !== '')
                         ? " WHERE o.status = '$status_filter'" : '';

        $orders_result = $this->conn->query(
            "SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
             FROM orders o JOIN users u ON o.user_id = u.id
             {$where} ORDER BY o.created_at DESC LIMIT $limit OFFSET $offset"
        );

        $orders = [];
        if ($orders_result) while ($r = $orders_result->fetch_assoc()) $orders[] = $r;

        $total_orders = (int)$this->conn->query("SELECT COUNT(*) as c FROM orders o{$where}")->fetch_assoc()['c'];

        // WhatsApp settings
        $wa_q        = $this->conn->query("SELECT * FROM whatsapp_settings WHERE id = 1");
        $wa_settings = $wa_q ? $wa_q->fetch_assoc() : null;
        $wa_enabled  = ($wa_settings && $wa_settings['is_enabled'] == 1);

        $this->render(__DIR__ . '/views/list.php', [
            'orders'        => $orders,
            'total_orders'  => $total_orders,
            'total_pages'   => ceil($total_orders / $limit),
            'page'          => $page,
            'status_filter' => $status_filter,
            'wa_enabled'    => $wa_enabled,
            'flash'         => $this->getFlash(),
        ]);
    }
}
