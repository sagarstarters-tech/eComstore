<?php
/**
 * WhatsApp Debug Tool — Admin Only
 * Shows the FULL raw Meta API response to diagnose delivery failures.
 * DELETE THIS FILE AFTER DEBUGGING.
 */
include 'admin_header.php';
?>
<div class="card border-0 shadow-sm">
<div class="card-body p-4">
<h4 class="fw-bold mb-1"><i class="fab fa-whatsapp text-success me-2"></i>WhatsApp API Diagnostic</h4>
<p class="text-muted mb-4">This shows the exact raw response from Meta's API so you can see why messages fail silently.</p>

<?php
// Fetch settings
$set_q = $conn->query("SELECT * FROM whatsapp_settings WHERE id = 1");
$settings = $set_q ? $set_q->fetch_assoc() : null;

if (!$settings) {
    echo '<div class="alert alert-danger">No WhatsApp settings found in DB.</div>';
    include 'admin_footer.php'; exit;
}

// Show current config (mask token)
$token     = trim($settings['api_token'] ?? '');
$phone_id  = trim($settings['phone_number_id'] ?? '');
$tpl_name  = trim($settings['meta_template_name'] ?? '');
$tpl_lang  = trim($settings['meta_template_lang'] ?? 'en');
$img_url   = trim($settings['wa_header_image_url'] ?? '');

$token_masked = $token ? substr($token, 0, 12) . '...' . substr($token, -4) : '(NOT SET)';
?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="p-3 bg-light rounded-3">
            <div class="small text-muted fw-bold mb-1">Phone Number ID</div>
            <div class="fw-bold"><?= $phone_id ?: '<span class="text-danger">NOT SET</span>' ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-light rounded-3">
            <div class="small text-muted fw-bold mb-1">API Token</div>
            <div class="fw-bold font-monospace"><?= htmlspecialchars($token_masked) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-light rounded-3">
            <div class="small text-muted fw-bold mb-1">Template Name</div>
            <div class="fw-bold"><?= $tpl_name ?: '<span class="text-warning">NOT SET (text mode)</span>' ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-light rounded-3">
            <div class="small text-muted fw-bold mb-1">Template Lang</div>
            <div class="fw-bold"><?= htmlspecialchars($tpl_lang) ?></div>
        </div>
    </div>
</div>

<?php if ($img_url): ?>
<div class="alert alert-info py-2 small mb-4">
    <i class="fas fa-image me-1"></i><strong>Header Image URL:</strong> <?= htmlspecialchars($img_url) ?>
</div>
<?php endif; ?>

<?php
// ── RUN LIVE API TEST ────────────────────────────────────────
$test_phone = trim($_POST['test_phone'] ?? '');
$run_test   = !empty($test_phone) && $_SERVER['REQUEST_METHOD'] === 'POST';

if ($run_test):
    // Normalize phone
    $clean_number = preg_replace('/[^0-9]/', '', $test_phone);
    if (strpos($clean_number, '0') === 0) $clean_number = ltrim($clean_number, '0');
    if (strlen($clean_number) == 10) $clean_number = '91' . $clean_number;
    
    // Get latest order
    $q = $conn->query("SELECT o.id, o.status, o.total_amount, u.name, 
                        (SELECT tracking_number FROM order_tracking WHERE order_id = o.id LIMIT 1) as tracking_number
                       FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.id DESC LIMIT 1");
    $order = $q ? $q->fetch_assoc() : null;
    if (!$order) {
        $order = ['id'=>1,'status'=>'processing','total_amount'=>999,'name'=>'Test Customer','tracking_number'=>'TEST123'];
    }
    
    $replacementValues = [
        '{CustomerName}' => trim($order['name']),
        '{OrderID}'      => $order['id'],
        '{OrderStatus}'  => ucwords(str_replace('_', ' ', $order['status'])),
        '{TrackingID}'   => $order['tracking_number'] ?: 'N/A',
        '{OrderAmount}'  => number_format($order['total_amount'], 2),
    ];
    
    $url = "https://graph.facebook.com/v19.0/{$phone_id}/messages";
    
    if (!empty($tpl_name)) {
        preg_match_all('/\{(CustomerName|OrderID|OrderStatus|TrackingID|OrderAmount)\}/', $settings['message_template'], $matches);
        $params = [];
        foreach (($matches[0] ?? []) as $varKey) {
            $params[] = ["type" => "text", "text" => (string)$replacementValues[$varKey]];
        }
        $components = [["type" => "body", "parameters" => $params]];
        if (!empty($img_url)) {
            array_unshift($components, [
                "type" => "header",
                "parameters" => [["type" => "image", "image" => ["link" => $img_url]]]
            ]);
        }
        $payload = [
            "messaging_product" => "whatsapp",
            "recipient_type"    => "individual",
            "to"                => $clean_number,
            "type"              => "template",
            "template"          => [
                "name"       => $tpl_name,
                "language"   => ["code" => $tpl_lang],
                "components" => $components,
            ]
        ];
    } else {
        $msg = $settings['message_template'];
        foreach ($replacementValues as $k => $v) $msg = str_replace($k, $v, $msg);
        $payload = [
            "messaging_product" => "whatsapp",
            "recipient_type"    => "individual",
            "to"                => $clean_number,
            "type"              => "text",
            "text"              => ["preview_url" => false, "body" => $msg]
        ];
    }
    
    // CALL API
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS,   json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER,   ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result      = curl_exec($ch);
    $http_code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error  = curl_error($ch);
    curl_close($ch);
    
    $resp = json_decode($result, true);
    $is_success = ($http_code == 200 && isset($resp['messages']));
    
    // Check for warnings/errors even on 200
    $msg_status = $resp['messages'][0]['message_status'] ?? null;
    $waba_id_resp = $resp['messages'][0]['id'] ?? null;
?>

<hr>
<h5 class="fw-bold mb-3">Test Results — Sent to: <code><?= htmlspecialchars($clean_number) ?></code></h5>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="p-3 rounded-3 <?= $is_success ? 'bg-success text-white' : 'bg-danger text-white' ?>">
            <div class="small fw-bold mb-1">HTTP Status Code</div>
            <div class="fs-3 fw-bold"><?= $http_code ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-light rounded-3">
            <div class="small text-muted fw-bold mb-1">Result</div>
            <div class="fw-bold <?= $is_success ? 'text-success' : 'text-danger' ?>">
                <?= $is_success ? '✅ API Accepted' : '❌ API Rejected' ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-light rounded-3">
            <div class="small text-muted fw-bold mb-1">Message ID</div>
            <div class="fw-bold font-monospace small"><?= $waba_id_resp ? htmlspecialchars($waba_id_resp) : 'N/A' ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-light rounded-3">
            <div class="small text-muted fw-bold mb-1">Message Status</div>
            <div class="fw-bold"><?= $msg_status ? htmlspecialchars($msg_status) : 'N/A' ?></div>
        </div>
    </div>
</div>

<?php if (!$is_success && isset($resp['error'])): ?>
<div class="alert alert-danger">
    <strong>Meta Error Code: <?= $resp['error']['code'] ?? 'N/A' ?></strong><br>
    <?= htmlspecialchars($resp['error']['message'] ?? 'Unknown error') ?><br>
    <?php if (isset($resp['error']['error_data']['details'])): ?>
        <small class="text-muted"><?= htmlspecialchars($resp['error']['error_data']['details']) ?></small>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($curl_error): ?>
<div class="alert alert-warning"><strong>cURL Error:</strong> <?= htmlspecialchars($curl_error) ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="form-label fw-bold small text-muted text-uppercase">📤 Payload Sent to Meta</label>
        <pre class="bg-dark text-success p-3 rounded-3 small"><?= json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></pre>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold small text-muted text-uppercase">📥 Raw Meta API Response</label>
        <pre class="bg-dark text-<?= $is_success ? 'success' : 'danger' ?> p-3 rounded-3 small"><?= json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
    </div>
</div>

<?php
// Give fix recommendations based on error code
$error_code = $resp['error']['code'] ?? null;
$error_msg  = $resp['error']['message'] ?? '';
if (!$is_success || $error_code):
?>
<div class="card border-warning mb-4">
<div class="card-header bg-warning bg-opacity-10 fw-bold">🔧 Possible Fix</div>
<div class="card-body">
<?php
$fixes = [
    190  => '<strong>Token Expired (190):</strong> Your Meta API token has expired. Go to <a href="https://developers.facebook.com/tools/explorer/" target="_blank">Meta Developer Tools</a> → Generate a new permanent token using System User or a long-lived token.',
    100  => '<strong>Invalid Parameter (100):</strong> The phone number format or template name is incorrect. Make sure the number has country code (e.g. 91XXXXXXXXXX) and the template name exactly matches what\'s in Meta Business Manager.',
    131030=> '<strong>Rate Limit (131030):</strong> Too many messages sent. Wait a few minutes and try again.',
    132000=> '<strong>Template Not Found (132000):</strong> The template name does not exist or has a typo. Check your Meta Business Manager → Message Templates.',
    132001=> '<strong>Template Not Approved (132001):</strong> Your template is still pending approval or was rejected by Meta.',
    131047=> '<strong>Re-engagement needed (131047):</strong> The recipient\'s number is outside the 24-hour window. You can only send template messages (which you are doing). This should work — check if the number is correct.',
    131026=> '<strong>Recipient Not on WhatsApp (131026):</strong> The phone number is not registered on WhatsApp.',
    131000=> '<strong>Something Went Wrong (131000):</strong> Generic Meta error. Check that your WABA (WhatsApp Business Account) is in good standing in Meta Business Manager.',
];
$fix_shown = false;
foreach ($fixes as $code => $fix_text) {
    if ($error_code == $code || str_contains($error_msg, (string)$code)) {
        echo '<p class="mb-0">' . $fix_text . '</p>';
        $fix_shown = true;
    }
}
if (!$fix_shown && !$is_success) {
    echo '<p class="mb-0"><strong>Unknown error.</strong> Check the raw response above. Common issues:<br>
    1. Token expired — regenerate at <a href="https://developers.facebook.com" target="_blank">developers.facebook.com</a><br>
    2. Phone not whitelisted (test mode needs <a href="https://developers.facebook.com/docs/whatsapp/cloud-api/get-started" target="_blank">whitelisted numbers</a>)<br>
    3. Template rejected — verify status in Meta Business Manager</p>';
}
if ($is_success) {
    echo '<div class="alert alert-success mb-0">✅ API accepted the message. If not delivered, possible reasons:
    <ul class="mb-0 mt-2">
    <li>Recipient\'s phone is off or has no internet</li>
    <li>Test mode: recipient number not in <strong>allowed test numbers list</strong> in Meta Developer Dashboard</li>
    <li>Template has <strong>image header</strong> but the image URL is not publicly accessible</li>
    <li>Number is correct but WhatsApp app needs to be active</li>
    </ul></div>';
}
?>
</div>
</div>
<?php endif; ?>

<?php endif; // end $run_test ?>

<form method="POST">
    <div class="mb-3">
        <label class="form-label fw-bold">Test Phone Number (with country code, no +)</label>
        <input type="text" name="test_phone" class="form-control" placeholder="919876543210" value="<?= htmlspecialchars($test_phone) ?>" required>
        <div class="form-text">Example: 919876543210 for India (+91 XXXXXXXXXX)</div>
    </div>
    <button type="submit" class="btn btn-success fw-bold px-4">
        <i class="fab fa-whatsapp me-2"></i>Send Test & Show Raw Response
    </button>
    <a href="manage_whatsapp_settings.php" class="btn btn-outline-secondary ms-2">← Back to Settings</a>
</form>

</div>
</div>
<?php include 'admin_footer.php'; ?>
