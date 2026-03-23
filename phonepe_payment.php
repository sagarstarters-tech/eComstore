<?php
// phonepe_payment.php
// This is included directly from checkout.php, so $conn, $order_id, $user_id, $grand_total, $global_settings, $user_data are available

// Fetch PhonePe settings
$merchantId = trim($global_settings['phonepe_merchant_id'] ?? '');
$saltKey = trim($global_settings['phonepe_salt_key'] ?? '');
$saltIndex = trim($global_settings['phonepe_salt_index'] ?? '1');
$mode = $global_settings['phonepe_mode'] ?? 'sandbox';

// Endpoint
if ($mode === 'live') {
    $apiUrl = 'https://api.phonepe.com/apis/hermes/pg/v1/pay';
} else {
    // For Sandbox/UAT
    $apiUrl = 'https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay';
}

// Generate unique Merchant Transaction ID
$merchantTransactionId = 'ORDER_' . $order_id . '_' . time();

// Amount in Paisa (Multiply by 100)
$amountInPaisa = (int)round($grand_total * 100);

// Host URL for redirects and webhooks
$siteUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
if (strpos($siteUrl, 'http') === 0) {
    // If SITE_URL is absolute (e.g. on Hostinger)
    $baseUrl = $siteUrl;
} else {
    // If SITE_URL is relative (e.g. /store in local) or empty
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host . $siteUrl;
}

$mobile = !empty($user_data['phone']) ? preg_replace('/[^0-9]/', '', $user_data['phone']) : '9999999999';
if(strlen($mobile) > 10) $mobile = substr($mobile, -10);

// Payload structure
$payload = [
    "merchantId" => $merchantId,
    "merchantTransactionId" => $merchantTransactionId,
    "merchantUserId" => "U" . $user_id,
    "amount" => $amountInPaisa,
    "redirectUrl" => $baseUrl . "/phonepe_success.php",
    "redirectMode" => "GET",
    "callbackUrl" => $baseUrl . "/phonepe-webhook.php",
    "mobileNumber" => $mobile,
    "paymentInstrument" => [
        "type" => "PAY_PAGE"
    ]
];

// Encode Payload
$jsonPayload = json_encode($payload);
$base64Payload = base64_encode($jsonPayload);

// Generate Checksum (X-VERIFY)
$verifyHeader = hash('sha256', $base64Payload . "/pg/v1/pay" . $saltKey) . "###" . $saltIndex;

// Insert pending transaction into phonepe_transactions
$stmt_trans = $conn->prepare("INSERT INTO phonepe_transactions (order_id, transaction_id, amount, status) VALUES (?, ?, ?, 'PENDING')");
$stmt_trans->bind_param("isd", $order_id, $merchantTransactionId, $grand_total);
$stmt_trans->execute();

// Store in session for recovery if redirect parameters are lost
$_SESSION['last_order_id'] = $order_id;
$_SESSION['last_merchant_txn_id'] = $merchantTransactionId;

// Send POST request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "X-VERIFY: " . $verifyHeader,
    "accept: application/json"
]);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['request' => $base64Payload]));

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    die("cURL Error during PhonePe initialization: " . htmlspecialchars($err));
} else {
    $res = json_decode($response, true);
    
    // Log the full initial response
    $full_resp = $conn->real_escape_string($response);
    $conn->query("UPDATE phonepe_transactions SET raw_payload = '$full_resp' WHERE transaction_id = '$merchantTransactionId'");
    
// ── Payment Page Redirection ──────────────────────────────────
if (isset($res['success']) && $res['success'] && isset($res['data']['instrumentResponse']['redirectInfo']['url'])) {
    $paymentUrl = $res['data']['instrumentResponse']['redirectInfo']['url'];
    
    // Redirect user to the Payment Page via HTTP Header
    // No output has been sent yet from checkout.php, so this is safe and better than JS
    header("Location: " . $paymentUrl);
    exit;
} else {
    // Log Error or handle gracefully
    $errorMsg = isset($res['message']) ? $res['message'] : 'Unknown error from PhonePe';
    
    // Include header if it wasn't already included (it shouldn't be if we are in this flow)
    if (!defined('HEADER_INCLUDED')) {
        include 'includes/header.php';
    }
    
    echo "<div class='container mt-5 pt-5 text-center'>";
    echo "<div class='card product-card p-5 shadow-sm border-0'>";
    echo "<i class='fas fa-exclamation-triangle fa-4x text-danger mb-4'></i>";
    echo "<h2 class='fw-bold mb-3'>Payment Initialization Failed</h2>";
    echo "<p class='text-muted mb-4'>" . htmlspecialchars($errorMsg) . "</p>";
    echo "<a href='checkout.php' class='btn btn-primary btn-custom px-5'>Go back to checkout</a>";
    echo "</div>";
    echo "</div>";
    include 'includes/footer.php';
    exit;
}
}
?>
