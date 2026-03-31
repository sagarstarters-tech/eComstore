<?php
/**
 * WhatsApp Integration Functions
 * Handles automated notifications via Meta Cloud API
 */

function sendAutomatedWhatsApp($conn, $order_id) {
    // Check if feature is globally enabled
    $set_q = $conn->query("SELECT * FROM whatsapp_settings WHERE id = 1");
    if (!$set_q || $set_q->num_rows === 0) return false;
    
    $settings = $set_q->fetch_assoc();
    
    // Auto-notifications ONLY for API mode, and must be enabled
    if ($settings['is_enabled'] != 1 || $settings['sending_mode'] !== 'api') {
        return false;
    }
    
    if (empty($settings['api_token']) || empty($settings['phone_number_id'])) {
        error_log("WhatsApp Auto-Send Failed: Missing API token or Phone ID");
        return false;
    }

    // Fetch Order details
    $order_id = intval($order_id);
    $q = $conn->query("
        SELECT o.status, o.total_amount, u.name, u.phone, 
               (SELECT tracking_number FROM order_tracking WHERE order_id = o.id LIMIT 1) as tracking_number
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = $order_id
    ");

    if (!$q || $q->num_rows === 0) return false;
    $order = $q->fetch_assoc();

    // Prepare variables
    $customerName = trim($order['name']);
    $customerPhone = trim($order['phone']);
    $orderStatus = ucwords(str_replace('_', ' ', $order['status']));
    $trackingID = !empty($order['tracking_number']) ? $order['tracking_number'] : 'N/A';
    $orderAmount = number_format($order['total_amount'], 2);

    // Meta API requires strict country code numeric formatting. E.g. India +91
    $clean_number = preg_replace('/[^0-9]/', '', $customerPhone);
    // Best effort validation for India prefix missing
    if (strlen($clean_number) == 10) {
        $clean_number = '91' . $clean_number;
    }

    if (empty($clean_number)) {
        return false;
    }

    // Parse Template
    $message = $settings['message_template'];
    $message = str_replace('{CustomerName}', $customerName, $message);
    $message = str_replace('{OrderID}', $order_id, $message);
    $message = str_replace('{OrderStatus}', $orderStatus, $message);
    $message = str_replace('{TrackingID}', $trackingID, $message);
    $message = str_replace('{OrderAmount}', $orderAmount, $message);

    // Prepare Meta API Payload
    $token = trim($settings['api_token']);
    $phone_id = trim($settings['phone_number_id']);
    
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

    // Fire cURL asynchronously (wait max 2 seconds to not block user flow)
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log the result
    $status_msg = "Unknown";
    if ($result) {
        $meta_response = json_decode($result, true);
        if ($http_code == 200 && isset($meta_response['messages'])) {
            $status_msg = 'Sent via Meta API (Auto)';
        } else {
            $error_desc = $meta_response['error']['message'] ?? 'Unknown Meta API Error';
            $status_msg = 'Failed API (Auto): ' . substr($error_desc, 0, 100);
        }
    } else {
        $status_msg = 'Failed API (Auto): Connection timeout';
    }

    // Insert into logs
    $stmt = $conn->prepare(
        "INSERT INTO whatsapp_logs (order_id, customer_number, message, sending_mode, status)
         VALUES (?, ?, ?, 'api', ?)"
    );
    if ($stmt) {
        $stmt->bind_param("isss", $order_id, $customerPhone, $message, $status_msg);
        $stmt->execute();
        $stmt->close();
    }

    return ($http_code == 200);
}
?>
