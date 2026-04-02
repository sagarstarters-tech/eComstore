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
    $orderStatus = ucwords(str_replace('_', ' ', $order['status'] ?? 'Processing'));
    $trackingID = !empty($order['tracking_number']) ? $order['tracking_number'] : 'N/A';
    $orderAmount = number_format($order['total_amount'] ?? 0, 2);

    // Meta API requires strict country code numeric formatting. E.g. India +91
    $clean_number = preg_replace('/[^0-9]/', '', $customerPhone);
    if (strpos($clean_number, '0') === 0) $clean_number = ltrim($clean_number, '0');
    if (strlen($clean_number) == 10) $clean_number = '91' . $clean_number;

    if (empty($clean_number)) return false;

    // Parse Template variables for payload and default text message
    $message = $settings['message_template'];
    $replacementValues = [
        '{CustomerName}' => $customerName,
        '{OrderID}'      => $order_id,
        '{OrderStatus}'  => $orderStatus,
        '{TrackingID}'   => $trackingID,
        '{OrderAmount}'  => $orderAmount
    ];
    
    // Create old style text message for fallback and logging
    foreach ($replacementValues as $search => $replace) {
        $message = str_replace($search, $replace, $message);
    }
    
    // Prepare Meta API Payload
    $token = trim($settings['api_token']);
    $phone_id = trim($settings['phone_number_id']);
    $url = "https://graph.facebook.com/v19.0/{$phone_id}/messages";
    
    $meta_template_name = $settings['meta_template_name'] ?? '';
    if (!empty($meta_template_name)) {
        // --- TEMPLATE MODE ---
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
                "name"     => trim($meta_template_name),
                "language" => ["code" => trim($settings['meta_template_lang'] ?? 'en')],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => $params
                    ]
                ]
            ]
        ];
    } else {
        // --- TEXT MODE ---
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $meta_response = json_decode($result, true);
    $status_msg = "";

    if ($http_code == 200 && isset($meta_response['messages'])) {
        $status_msg = 'Sent via Meta API (Auto)';
    } else {
        $error_desc = $meta_response['error']['message'] ?? 'Connection error or unknown Meta API Error';
        $error_code = $meta_response['error']['code'] ?? 'N/A';
        $status_msg = "Failed API (Auto): (#{$error_code}) " . substr($error_desc, 0, 100);
        
        // Log deep error for admin
        $log_dir = __DIR__ . '/../logs';
        if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
        $log_entry = '[' . date('Y-m-d H:i:s') . "] Order #$order_id API Error: (#$error_code) $error_desc" . PHP_EOL;
        $log_entry .= "Payload: " . json_encode($payload) . PHP_EOL;
        $log_entry .= "Response: " . $result . PHP_EOL;
        file_put_contents($log_dir . '/whatsapp_errors.log', $log_entry, FILE_APPEND);
    }
    
    // Log to Database using generic INSERT
    $conn->query("INSERT INTO whatsapp_logs (order_id, customer_number, message, sending_mode, status) VALUES ($order_id, '$clean_number', '" . $conn->real_escape_string($message) . "', 'api', '$status_msg')");
    
    return $http_code == 200;
}
?>
