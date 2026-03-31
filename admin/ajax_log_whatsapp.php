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

if ($sending_mode === 'api') {
    // Fetch API Settings
    $set_q = $conn->query("SELECT api_token, phone_number_id FROM whatsapp_settings WHERE id = 1");
    $settings = $set_q->fetch_assoc();
    
    if (empty($settings['api_token']) || empty($settings['phone_number_id'])) {
        $status = "Failed: Missing API Token or Phone Number ID";
        $stmt->bind_param("issss", $order_id, $customer_number, $message, $sending_mode, $status);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => false, 'error' => 'API Token or Phone Number ID is missing in settings.']);
        exit;
    }

    $token = trim($settings['api_token']);
    $phone_id = trim($settings['phone_number_id']);
    
    // Normalize customer number (Meta expects just digits starting with country code, e.g., 919876543210)
    $clean_number = preg_replace('/[^0-9]/', '', $customer_number);

    $url = "https://graph.facebook.com/v19.0/{$phone_id}/messages";
    
    $payload = [
        "messaging_product" => "whatsapp",
        "recipient_type"    => "individual",
        "to"                => $clean_number,
        "type"              => "text",
        "text"              => [
            "preview_url" => false,
            "body"        => $message
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $meta_response = json_decode($result, true);
    
    if ($http_code == 200 && isset($meta_response['messages'])) {
        $status = 'Sent via Meta API';
        $stmt->bind_param("issss", $order_id, $customer_number, $message, $sending_mode, $status);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        $error_desc = $meta_response['error']['message'] ?? 'Unknown Meta API Error';
        $status = 'Failed: ' . substr($error_desc, 0, 100);
        $stmt->bind_param("issss", $order_id, $customer_number, $message, $sending_mode, $status);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => false, 'error' => "Meta API Error: " . $error_desc]);
    }
} else {
    // Web Mode
    $stmt->bind_param("issss", $order_id, $customer_number, $message, $sending_mode, $status);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    $stmt->close();
}
