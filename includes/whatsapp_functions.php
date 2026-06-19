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
        
        // Build components array (body is always included)
        $components = [
            [
                "type" => "body",
                "parameters" => $params
            ]
        ];
        
        // Add header image component if configured
        $header_image_url = trim($settings['wa_header_image_url'] ?? '');
        if (!empty($header_image_url)) {
            array_unshift($components, [
                "type" => "header",
                "parameters" => [
                    [
                        "type" => "image",
                        "image" => ["link" => $header_image_url]
                    ]
                ]
            ]);
        }
        
        $payload = [
            "messaging_product" => "whatsapp",
            "recipient_type"    => "individual",
            "to"                => $clean_number,
            "type"              => "template",
            "template"          => [
                "name"     => trim($meta_template_name),
                "language" => ["code" => trim($settings['meta_template_lang'] ?? 'en')],
                "components" => $components
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
    curl_setopt($ch, CURLOPT_POSTFIELDS,    json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER,    [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT,        15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result     = curl_exec($ch);
    $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Always log every API call for diagnosis
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
    $log_entry = '[' . date('Y-m-d H:i:s') . "] Auto-Send Order#$order_id HTTP:{$http_code} To:{$clean_number}" . PHP_EOL;
    $log_entry .= "Payload: " . json_encode($payload) . PHP_EOL;
    $log_entry .= "Response: " . $result . PHP_EOL;
    $log_entry .= str_repeat('-', 60) . PHP_EOL;
    file_put_contents($log_dir . '/whatsapp_api.log', $log_entry, FILE_APPEND);
    
    if ($curl_error) {
        error_log("[WhatsApp] cURL Error Order#$order_id: $curl_error");
        $conn->query("INSERT INTO whatsapp_logs (order_id, customer_number, message, sending_mode, status) VALUES ($order_id, '$clean_number', '" . $conn->real_escape_string($message) . "', 'api', 'Failed: cURL - " . $conn->real_escape_string(substr($curl_error,0,80)) . "')");
        return false;
    }
    
    $meta_response = json_decode($result, true);
    $status_msg = "";

    if ($http_code == 200 && isset($meta_response['messages'])) {
        $msg_id  = $meta_response['messages'][0]['id'] ?? 'unknown';
        $status_msg = 'Sent via Meta API (Auto) ID:' . substr($msg_id, 0, 20);
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

/**
 * Send WhatsApp notification to ADMIN when a new order is placed.
 * This is independent of the customer notification (sendAutomatedWhatsApp).
 * Uses text message mode for admin (no Meta template needed).
 * Fail-safe: errors are logged but never block order completion.
 *
 * @param mysqli $conn    Database connection
 * @param int    $order_id The order ID
 * @return bool  True if sent successfully
 */
function sendAdminOrderNotification($conn, $order_id) {
    try {
        // Check if feature is enabled and admin number is configured
        $set_q = $conn->query("SELECT * FROM whatsapp_settings WHERE id = 1");
        if (!$set_q || $set_q->num_rows === 0) return false;
        
        $settings = $set_q->fetch_assoc();
        
        // Must have: global enabled + API mode + admin notification enabled + admin number set
        if ($settings['is_enabled'] != 1 || $settings['sending_mode'] !== 'api') {
            return false;
        }
        if (empty($settings['admin_notify_on_new_order']) || $settings['admin_notify_on_new_order'] != 1) {
            return false;
        }
        $admin_number = trim($settings['admin_whatsapp_number'] ?? '');
        if (empty($admin_number)) {
            return false;
        }
        if (empty($settings['api_token']) || empty($settings['phone_number_id'])) {
            error_log("[WhatsApp Admin] Failed: Missing API token or Phone ID");
            return false;
        }

        // Clean admin number (ensure country code format)
        $clean_admin = preg_replace('/[^0-9]/', '', $admin_number);
        if (strpos($clean_admin, '0') === 0) $clean_admin = ltrim($clean_admin, '0');
        if (strlen($clean_admin) == 10) $clean_admin = '91' . $clean_admin;
        
        if (empty($clean_admin)) return false;

        // Fetch order details for admin message
        $order_id = intval($order_id);
        $q = $conn->query("
            SELECT o.id, o.status, o.total_amount, o.payment_mode, o.created_at,
                   u.name AS customer_name, u.phone AS customer_phone, u.email AS customer_email
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = $order_id
        ");

        if (!$q || $q->num_rows === 0) return false;
        $order = $q->fetch_assoc();

        // Build admin notification message
        $customerName  = trim($order['customer_name']);
        $customerPhone = trim($order['customer_phone']);
        $orderAmount   = number_format($order['total_amount'] ?? 0, 2);
        $paymentMode   = strtoupper($order['payment_mode'] ?? 'N/A');
        $orderTime     = date('d M Y, h:i A', strtotime($order['created_at']));

        $adminMessage  = "🛒 *New Order Alert!*\n\n";
        $adminMessage .= "Order: *#$order_id*\n";
        $adminMessage .= "Customer: $customerName\n";
        $adminMessage .= "Phone: $customerPhone\n";
        $adminMessage .= "Amount: ₹$orderAmount\n";
        $adminMessage .= "Payment: $paymentMode\n";
        $adminMessage .= "Time: $orderTime\n\n";
        $adminMessage .= "Login to admin panel to process this order.";

        // Send via Meta Cloud API (text mode — no template needed)
        $token    = trim($settings['api_token']);
        $phone_id = trim($settings['phone_number_id']);
        $url      = "https://graph.facebook.com/v19.0/{$phone_id}/messages";

        $payload = [
            "messaging_product" => "whatsapp",
            "recipient_type"    => "individual",
            "to"                => $clean_admin,
            "type"              => "text",
            "text"              => ["preview_url" => false, "body" => $adminMessage]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS,    json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER,     [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT,        15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result     = curl_exec($ch);
        $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        // Log to file
        $log_dir = __DIR__ . '/../logs';
        if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
        $log_entry  = '[' . date('Y-m-d H:i:s') . "] ADMIN-Notify Order#$order_id HTTP:{$http_code} To:{$clean_admin}" . PHP_EOL;
        $log_entry .= "Payload: " . json_encode($payload) . PHP_EOL;
        $log_entry .= "Response: " . $result . PHP_EOL;
        $log_entry .= str_repeat('-', 60) . PHP_EOL;
        file_put_contents($log_dir . '/whatsapp_api.log', $log_entry, FILE_APPEND);

        // Determine status
        $status_msg = '';
        if ($curl_error) {
            error_log("[WhatsApp Admin] cURL Error Order#$order_id: $curl_error");
            $status_msg = 'Admin Failed: cURL - ' . substr($curl_error, 0, 80);
        } else {
            $meta_response = json_decode($result, true);
            if ($http_code == 200 && isset($meta_response['messages'])) {
                $msg_id     = $meta_response['messages'][0]['id'] ?? 'unknown';
                $status_msg = 'Admin Sent via Meta API ID:' . substr($msg_id, 0, 20);
            } else {
                $error_desc = $meta_response['error']['message'] ?? 'Unknown Meta API Error';
                $error_code = $meta_response['error']['code'] ?? 'N/A';
                $status_msg = "Admin Failed API: (#{$error_code}) " . substr($error_desc, 0, 100);
            }
        }

        // Log to whatsapp_logs table
        $conn->query("INSERT INTO whatsapp_logs (order_id, customer_number, message, sending_mode, status) 
            VALUES ($order_id, '$clean_admin', '" . $conn->real_escape_string($adminMessage) . "', 'api', '" . $conn->real_escape_string($status_msg) . "')");

        return $http_code == 200;

    } catch (Exception $e) {
        // Fail-safe: never block order completion
        error_log("[WhatsApp Admin] Exception Order#$order_id: " . $e->getMessage());
        return false;
    }
}
?>
