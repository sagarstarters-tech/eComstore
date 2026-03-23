<?php
include_once __DIR__ . '/../includes/session_setup.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// ── Auth guard: must be logged-in admin ─────────────────────
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$order_id      = intval($_POST['order_id'] ?? 0);
$customer_number = trim($_POST['customer_number'] ?? '');
$message         = trim($_POST['message'] ?? '');
$sending_mode    = trim($_POST['sending_mode'] ?? '');

// Whitelist sending_mode to avoid arbitrary data
$allowed_modes = ['web', 'api'];
if (!in_array($sending_mode, $allowed_modes, true)) {
    $sending_mode = 'web';
}

$status = ($sending_mode === 'api') ? 'Sent via API' : 'Sent via Web';

// ── Prepared statement — prevents SQL injection ──────────────
$stmt = $conn->prepare(
    "INSERT INTO whatsapp_logs (order_id, customer_number, message, sending_mode, status)
     VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param("issss", $order_id, $customer_number, $message, $sending_mode, $status);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    error_log('[WhatsApp Log] DB error: ' . $conn->error);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
$stmt->close();
