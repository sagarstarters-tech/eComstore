<?php
/**
 * AWB Shipment Tracking Proxy Page
 * 
 * This securely proxies the courier tracking page for embedding.
 * Since most courier sites block iframe embedding (X-Frame-Options),
 * this page opens the tracking URL in a new tab or renders a
 * server-side status check via cURL if available.
 * 
 * Usage: awb_track.php?order_id=123&email=user@example.com
 *        (for customer access — validates ownership)
 * 
 *        awb_track.php?order_id=123&admin=1
 *        (for admin access — validates admin session)
 */

include_once __DIR__ . '/../includes/session_setup.php';
include_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

$order_id = intval($_GET['order_id'] ?? 0);
$is_admin = isset($_GET['admin']) && $_GET['admin'] == '1';

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Order ID is required.']);
    exit;
}

// --- Access Control ---
if ($is_admin) {
    // Admin session check
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
        exit;
    }
} else {
    // Customer access: validate email ownership
    $email = trim($_GET['email'] ?? '');
    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Email is required for customer tracking.']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT o.id FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? AND u.email = ?");
    $stmt->bind_param("is", $order_id, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Order not found or email mismatch.']);
        exit;
    }
    $stmt->close();
}

// --- Fetch tracking data ---
$stmt = $conn->prepare("
    SELECT t.tracking_number, c.name as courier_name, c.tracking_url_base 
    FROM order_tracking t 
    LEFT JOIN courier_companies c ON t.courier_id = c.id 
    WHERE t.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$tracking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tracking || empty($tracking['tracking_number'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No AWB/tracking number assigned to this order yet.'
    ]);
    exit;
}

$awb = $tracking['tracking_number'];
$courier_name = $tracking['courier_name'] ?? 'Unknown';
$tracking_url = null;

if (!empty($tracking['tracking_url_base'])) {
    $tracking_url = $tracking['tracking_url_base'] . urlencode($awb);
}

echo json_encode([
    'status' => 'success',
    'data' => [
        'awb' => $awb,
        'courier_name' => $courier_name,
        'tracking_url' => $tracking_url,
        'order_id' => $order_id
    ]
]);
exit;
