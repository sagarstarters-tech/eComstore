<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// ── Auth guard: must be logged-in admin ─────────────────────
include_once __DIR__ . '/../includes/session_setup.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit;
}

if (!isset($_GET['order_id'])) {
    echo json_encode(['error' => 'Missing Order ID']);
    exit;
}

$order_id = intval($_GET['order_id']);

// Get settings
$set_q = $conn->query("SELECT * FROM whatsapp_settings WHERE id = 1");
$settings = $set_q ? $set_q->fetch_assoc() : null;

if (!$settings || !$settings['is_enabled']) {
    echo json_encode(['error' => 'WhatsApp Notifications are disabled.']);
    exit;
}

// Get order details — prepared statement, no SQL injection
$order_stmt = $conn->prepare(
    "SELECT o.*, u.name as customer_name, u.phone as customer_phone
     FROM orders o JOIN users u ON o.user_id = u.id
     WHERE o.id = ?"
);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_res = $order_stmt->get_result();
$order_stmt->close();

if (!$order_res || $order_res->num_rows === 0) {
    echo json_encode(['error' => 'Order not found.']);
    exit;
}

$order = $order_res->fetch_assoc();

// Tracking number — prepared statement
$tracking_id = 'N/A';
$track_stmt = $conn->prepare("SELECT tracking_number FROM order_tracking WHERE order_id = ? LIMIT 1");
$track_stmt->bind_param("i", $order_id);
$track_stmt->execute();
$track_res = $track_stmt->get_result();
$track_stmt->close();
if ($track_res && $track_res->num_rows > 0) {
    $track = $track_res->fetch_assoc();
    $tracking_id = $track['tracking_number'] ?: 'N/A';
}

$template = $settings['message_template'];

// Replace variables in template
$message = str_replace(
    ['{CustomerName}', '{OrderID}', '{OrderStatus}', '{TrackingID}', '{OrderAmount}'],
    [$order['customer_name'], $order['id'], ucfirst($order['status']), $tracking_id, number_format($order['total_amount'], 2)],
    $template
);

echo json_encode([
    'success'        => true,
    'message'        => $message,
    'customer_phone' => $order['customer_phone'] ?: '',
    'sending_mode'   => $settings['sending_mode'],
    // api_token intentionally omitted — never expose secrets in GET AJAX response
]);
