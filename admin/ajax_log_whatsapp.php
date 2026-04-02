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
    // Fetch Complete Settings
    $set_q = $conn->query("SELECT * FROM whatsapp_settings WHERE id = 1");
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
    $meta_template_name = $settings['meta_template_name'] ?? '';
    
    // Normalize customer number
    $clean_number = preg_replace('/[^0-9]/', '', $customer_number);
    $url = "https://graph.facebook.com/v19.0/{$phone_id}/messages";
    
    if (!empty($meta_template_name)) {
        // --- TEMPLATE MODE ---
        // 1. We need to fetch variables to populate the template
        // Note: The message passed from modal is the ALREADY REPLACED text.
        // But for Meta API templates, we need the raw parameters back.
        // We reuse the mapping in the 'includes/whatsapp_functions.php' logic.
        
        $q = $conn->query("
            SELECT o.id, o.status, o.total_amount, u.name, 
                   (SELECT tracking_number FROM order_tracking WHERE order_id = o.id LIMIT 1) as tracking_number
            FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $order_id
        ");
        $order = $q->fetch_assoc();
        
        $replacementValues = [
            '{CustomerName}' => trim($order['name']),
            '{OrderID}'      => $order['id'],
            '{OrderStatus}'  => ucwords(str_replace('_', ' ', $order['status'])),
            '{TrackingID}'   => $order['tracking_number'] ?: 'N/A',
            '{OrderAmount}'  => number_format($order['total_amount'], 2)
        ];

        preg_match_all('/\{(CustomerName|OrderID|OrderStatus|TrackingID|OrderAmount)\}/', $settings['message_template'], $matches);
        
        $params = [];
        if (!empty($matches[0])) {
            foreach ($matches[0] as $varKey) {
                $params[] = ["type" => "text", "text" => (string)$replacementValues[$varKey]];
            }
        }

        $payload = [
            "messaging_product" => "whatsapp",
            "recipient_type"    => "individual",
            "to"                => $clean_number,
            "type"              => "template",
            "template"          => [
                "name"     => $meta_template_name,
                "language" => ["code" => $settings['meta_template_lang'] ?? 'en'],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => $params
                    ]
                ]
            ]
        ];
    } else {
        // --- PLAIN TEXT MODE ---
        $payload = [
            "messaging_product" => "whatsapp",
            "recipient_type"    => "individual",
            "to"                => $clean_number,
            "type"              => "text",
            "text"              => ["preview_url" => false, "body" => $message]
        ];
    }

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
        $error_code = $meta_response['error']['code'] ?? 'N/A';
        $status = "Failed API: (#{$error_code}) " . substr($error_desc, 0, 100);
        
        // Log deep error
        $log_dir = __DIR__ . '/../logs';
        if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
        $log_entry = '[' . date('Y-m-d H:i:s') . "] Manual Order #$order_id API Error: (#$error_code) $error_desc" . PHP_EOL;
        $log_entry .= "Payload: " . json_encode($payload) . PHP_EOL;
        $log_entry .= "Response: " . $result . PHP_EOL;
        file_put_contents($log_dir . '/whatsapp_errors.log', $log_entry, FILE_APPEND);

        $stmt->bind_param("issss", $order_id, $customer_number, $message, $sending_mode, $status);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => false, 'error' => "Meta API Error (#$error_code): " . $error_desc]);
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
