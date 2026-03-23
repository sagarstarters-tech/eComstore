<?php
namespace TrackingModule\Repositories;

use PDO;
use Exception;

class TrackingRepository {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // --- Customer Facing ---
    
    public function getTrackingDetailsByOrder($order_id) {
        $stmt = $this->db->prepare("
            SELECT t.*, c.name as courier_name, c.tracking_url_base, o.status as current_status, o.created_at as order_date
            FROM order_tracking t
            LEFT JOIN courier_companies c ON t.courier_id = c.id
            JOIN orders o ON t.order_id = o.id
            WHERE t.order_id = :order_id
        ");
        $stmt->execute([':order_id' => $order_id]);
        return $stmt->fetch();
    }

    public function getOrderStatusHistory($order_id) {
        $stmt = $this->db->prepare("
            SELECT status, notes, created_at, logged_by
            FROM order_status_history
            WHERE order_id = :order_id
            ORDER BY created_at DESC
        ");
        $stmt->execute([':order_id' => $order_id]);
        return $stmt->fetchAll();
    }

    public function verifyOrderBelongsToEmail($order_id, $email) {
        $stmt = $this->db->prepare("
            SELECT o.id 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = :order_id AND u.email = :email
        ");
        $stmt->execute([':order_id' => $order_id, ':email' => $email]);
        return $stmt->fetchColumn() !== false;
    }

    // --- Admin Facing ---

    public function getActiveCouriers() {
        $stmt = $this->db->prepare("SELECT id, name, tracking_url_base FROM courier_companies WHERE is_active = 1 ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function upsertTrackingInfo($order_id, $courier_id, $tracking_number, $estimated_delivery_date) {
        $stmt = $this->db->prepare("
            INSERT INTO order_tracking (order_id, courier_id, tracking_number, estimated_delivery_date)
            VALUES (:order_id, :courier_id, :tracking_number, :estimated_delivery_date)
            ON DUPLICATE KEY UPDATE
            courier_id = VALUES(courier_id),
            tracking_number = VALUES(tracking_number),
            estimated_delivery_date = VALUES(estimated_delivery_date)
        ");
        
        return $stmt->execute([
            ':order_id' => $order_id,
            ':courier_id' => $courier_id ?: null,
            ':tracking_number' => $tracking_number ?: null,
            ':estimated_delivery_date' => $estimated_delivery_date ?: null
        ]);
    }

    public function logStatusChange($order_id, $status, $notes = null, $logged_by = 'admin') {
        $stmt = $this->db->prepare("
            INSERT INTO order_status_history (order_id, status, notes, logged_by)
            VALUES (:order_id, :status, :notes, :logged_by)
        ");
        return $stmt->execute([
            ':order_id' => $order_id,
            ':status' => $status,
            ':notes' => $notes,
            ':logged_by' => $logged_by
        ]);
    }

    public function updateOrderStatus($order_id, $status) {
        $stmt = $this->db->prepare("UPDATE orders SET status = :status WHERE id = :order_id");
        return $stmt->execute([
            ':status' => $status,
            ':order_id' => $order_id
        ]);
    }

    public function clearAllStatusHistory() {
        return $this->db->exec("TRUNCATE TABLE order_status_history");
    }
}
