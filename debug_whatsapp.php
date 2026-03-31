<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db_connect.php';
require_once 'includes/whatsapp_functions.php';

echo "<h2>WhatsApp Cloud API Diagnostic Tool</h2>";

// 1. Check Settings
$set_q = $conn->query("SELECT * FROM whatsapp_settings WHERE id = 1");
$settings = $set_q->fetch_assoc();

echo "<h4>1. Configuration Check</h4>";
echo "Feature Enabled: " . ($settings['is_enabled'] ? 'YES' : 'NO') . "<br>";
echo "Sending Mode: " . $settings['sending_mode'] . "<br>";
echo "Phone Number ID: " . (!empty($settings['phone_number_id']) ? 'PRESENT (' . strlen($settings['phone_number_id']) . ' chars)' : 'MISSING') . "<br>";
echo "API Token: " . (!empty($settings['api_token']) ? 'PRESENT' : 'MISSING') . "<br>";

// 2. Check cURL
echo "<h4>2. CURL Check</h4>";
if (function_exists('curl_version')) {
    echo "CURL is ENABLED.<br>";
} else {
    echo "<span style='color:red'>CURL is DISABLED. Automated WhatsApp will not work!</span><br>";
}

// 3. Test send to a specific order
$order_id_q = $conn->query("SELECT id FROM orders ORDER BY created_at DESC LIMIT 1");
if ($order_id_q && $order_id_q->num_rows > 0) {
    $order_id = $order_id_q->fetch_assoc()['id'];
    echo "<h4>3. Test Sending for Order #$order_id</h4>";
    echo "Triggering sendAutomatedWhatsApp($order_id)...<br>";
    
    $result = sendAutomatedWhatsApp($conn, $order_id);
    
    if ($result) {
        echo "<span style='color:green'>SUCCESS! The message was accepted by Meta.</span><br>";
    } else {
        echo "<span style='color:red'>FAILED. Check the error log below.</span><br>";
    }
} else {
    echo "<h4>3. Test Sending</h4>";
    echo "No orders found to test with.<br>";
}

// 4. View Logs
echo "<h4>4. Recent Logs</h4>";
$logs = $conn->query("SELECT * FROM whatsapp_logs ORDER BY sent_at DESC LIMIT 5");
while ($log = $logs->fetch_assoc()) {
    $color = (strpos($log['status'], 'Failed') !== false) ? 'red' : 'green';
    echo "[$log[sent_at]] To: $log[customer_number] | Status: <span style='color:$color'>$log[status]</span><br>";
}

// 5. Raw Error Log
echo "<h4>5. Raw System Error Log (logs/whatsapp_errors.log)</h4>";
$error_log = 'logs/whatsapp_errors.log';
if (file_exists($error_log)) {
    echo "<pre style='background:#f4f4f4; padding:15px; overflow:auto; max-height: 400px;'>" . htmlspecialchars(file_get_contents($error_log)) . "</pre>";
} else {
    echo "No log file found. This might mean the code was never triggered or couldn't write.";
}
?>
