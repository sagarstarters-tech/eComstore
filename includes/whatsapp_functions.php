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
    
    // Remove leading zero if it exists (common for local numbers)
    if (strpos($clean_number, '0') === 0) {
        $clean_number = ltrim($clean_number, '0');
    }

    // Best effort validation for India prefix missing
    if (strlen($clean_number) == 10) {
        $clean_number = '91' . $clean_number;
    }

    if (empty($clean_number)) {
        return false;
    }
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
    
    // Check if user has an approved Meta Template Name configured
    $meta_template_name = $settings['meta_template_name'] ?? '';
    if (!empty($meta_template_name)) {
        // Build template component list by extracting `{Variable}` tags in the EXACT order they appear in user's UI.
        // This maps the UI variables to Meta's {{1}}, {{2}} automatically.
        preg_match_all('/\{(CustomerName|OrderID|OrderStatus|TrackingID|OrderAmount)\}/', $settings['message_template'], $matches);
        
        $params = [];
        if (!empty($matches[0])) {
            foreach ($matches[0] as $varKey) {
                // Limit status strings if necessary, though WhatsApp handles regular strings fine
                $params[] = [
                    "type" => "text",
                    "text" => (string)$replacementValues[$varKey]
                ];
            }
        }
        
        $payload = [
            "messaging_product" => "whatsapp",
            "recipient_type"    => "individual",
            "to"                => $clean_number,
            "type"              => "template",
            "template"          => [
                "name"     => trim($meta_template_name),
                "language" => [
                    "code" => trim($settings['meta_template_lang'] ?? 'en')
                ]
            ]
        ];
        
        if (!empty($params)) {
            $payload["template"]["components"] = [
                [
                    "type" => "body",
                    "parameters" => $params
                ]
            ];
        }
    } else {
        // Fallback or Web Mode Legacy Text Structure
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
    }
    // Fire cURL asynchronously (wait max 2 seconds to not block user flow)
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
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

    // Ensure logs table exists
    $conn->query("CREATE TABLE IF NOT EXISTS `whatsapp_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` int(11) NOT NULL,
        `customer_number` varchar(50) NOT NULL,
        `message` text NOT NULL,
        `sending_mode` varchar(20) NOT NULL,
        `status` varchar(255) NOT NULL,
        `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Insert into logs
    $stmt = $conn->prepare(
        "INSERT INTO whatsapp_logs (order_id, customer_number, message, sending_mode, status)
         VALUES (?, ?, ?, 'api', ?)"
    );
    if ($stmt) {
        $stmt->bind_param("isss", $order_id, $customerPhone, $message, $status_msg);
        $stmt->execute();
        $stmt->close();
    } else {
        // Log query failure
        $log_dir = __DIR__ . '/../logs'; // root/logs
        if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
        file_put_contents($log_dir . '/whatsapp_errors.log', '[' . date('Y-m-d H:i:s') . '] DB Error: ' . $conn->error . PHP_EOL, FILE_APPEND);
    }
    
    // Detailed API Logging if failed
    if (strpos($status_msg, 'Failed') !== false) {
        $log_dir = __DIR__ . '/../logs';
        if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
        $log_entry = '[' . date('Y-m-d H:i:s') . "] Order #$order_id Error: $status_msg" . PHP_EOL;
        $log_entry .= "Payload: " . json_encode($payload) . PHP_EOL;
        file_put_contents($log_dir . '/whatsapp_errors.log', $log_entry, FILE_APPEND);
    }

    return ($http_code == 200);
}
?>
