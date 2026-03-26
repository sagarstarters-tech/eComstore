<?php
require_once 'admin_header.php';

// Ensure chat widget columns exist (safe to run every time)
$conn->query("ALTER TABLE whatsapp_settings ADD COLUMN IF NOT EXISTS chat_widget_enabled TINYINT(1) NOT NULL DEFAULT 1");
$conn->query("ALTER TABLE whatsapp_settings ADD COLUMN IF NOT EXISTS chat_widget_number VARCHAR(20) NOT NULL DEFAULT ''");
$conn->query("ALTER TABLE whatsapp_settings ADD COLUMN IF NOT EXISTS chat_widget_message VARCHAR(255) NOT NULL DEFAULT 'Hello, I have a question about your products.'");

// Fetch current settings
$settings_query = "SELECT * FROM whatsapp_settings WHERE id = 1";
$result = $conn->query($settings_query);
$settings = $result->fetch_assoc();

if (!$settings) {
    // Failsafe insert if missing
    $conn->query("INSERT IGNORE INTO whatsapp_settings (id, message_template) VALUES (1, 'Hello {CustomerName},\n\nYour Order #{OrderID} status has been updated.\n\nCurrent Status: *{OrderStatus}*\nTracking ID: {TrackingID}\nTotal Amount: ₹{OrderAmount}\n\nThank you for shopping with us.')");
    $settings = ['is_enabled'=>1, 'sender_number'=>'', 'api_token'=>'', 'sending_mode'=>'web', 'message_template'=>'Hello {CustomerName},\n\nYour Order #{OrderID} status has been updated.\n\nCurrent Status: *{OrderStatus}*\nTracking ID: {TrackingID}\nTotal Amount: ₹{OrderAmount}\n\nThank you for shopping with us.', 'chat_widget_enabled'=>1, 'chat_widget_number'=>'', 'chat_widget_message'=>'Hello, I have a question about your products.'];
}

$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    $sender_number = $conn->real_escape_string($_POST['sender_number']);
    $api_token = $conn->real_escape_string($_POST['api_token']);
    $sending_mode = $conn->real_escape_string($_POST['sending_mode']);
    $message_template = $conn->real_escape_string($_POST['message_template']);
    $is_enabled = isset($_POST['is_enabled']) ? 1 : 0;

    // Chat Widget fields
    $chat_widget_enabled = isset($_POST['chat_widget_enabled']) ? 1 : 0;
    $chat_widget_number  = $conn->real_escape_string($_POST['chat_widget_number'] ?? '');
    $chat_widget_message = $conn->real_escape_string($_POST['chat_widget_message'] ?? 'Hello, I have a question about your products.');

    $update_query = "UPDATE whatsapp_settings SET 
        is_enabled = $is_enabled,
        sender_number = '$sender_number',
        api_token = '$api_token',
        sending_mode = '$sending_mode',
        message_template = '$message_template',
        chat_widget_enabled = $chat_widget_enabled,
        chat_widget_number = '$chat_widget_number',
        chat_widget_message = '$chat_widget_message'
        WHERE id = 1";

    if ($conn->query($update_query)) {
        $success_msg = "WhatsApp Settings updated successfully.";
        // Refresh settings
        $result = $conn->query($settings_query);
        $settings = $result->fetch_assoc();
    }
}

// Fetch logs
$logs_query = "SELECT wl.*, o.id as order_number, u.name as customer_name FROM whatsapp_logs wl JOIN orders o ON wl.order_id = o.id JOIN users u ON o.user_id = u.id ORDER BY wl.sent_at DESC LIMIT 50";
$logs = $conn->query($logs_query);
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="mb-0 text-dark fw-bold"><i class="fab fa-whatsapp me-2 text-success"></i> WhatsApp Notifications</h2>
        <p class="text-muted mb-0">Manage automated order status updates via WhatsApp.</p>
    </div>
</div>

<?php if ($success_msg): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?>
    <button type="button" class="btn-close" data-mdb-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-7">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <form method="POST">
    <?php echo csrf_input(); ?>
                    <input type="hidden" name="action" value="update_settings">
                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                        <h5 class="fw-bold m-0">General Configuration</h5>
                        <div class="form-check form-switch fs-5 m-0">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_enabled" id="enableWhatsapp" <?php echo ($settings['is_enabled']) ? 'checked' : ''; ?>>
                            <label class="form-check-label ms-2 fs-6 fw-bold" for="enableWhatsapp">Enable Feature</label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Sending Mode</label>
                        <select name="sending_mode" class="form-select bg-light">
                            <option value="web" <?php echo ($settings['sending_mode'] === 'web') ? 'selected' : ''; ?>>WhatsApp Web (Manual Redirect)</option>
                            <option value="api" <?php echo ($settings['sending_mode'] === 'api') ? 'selected' : ''; ?>>WhatsApp Business API (Automated)</option>
                        </select>
                        <small class="text-muted">Web mode opens WhatsApp Web/Desktop safely. API mode requires official Meta credentials.</small>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 text-start">
                            <label class="form-label fw-bold d-block">Sender Number</label>
                            <?php echo render_phone_input('sender_number', $settings['sender_number'], true); ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Business API Token <small class="text-muted">(Optional)</small></label>
                            <input type="password" name="api_token" class="form-control bg-light" placeholder="EAAI..." value="<?php echo htmlspecialchars($settings['api_token']); ?>">
                            <div class="form-check mt-1">
                                <input class="form-check-input show-password-toggle" type="checkbox" id="showPwWhatsapp">
                                <label class="form-check-label small text-muted" for="showPwWhatsapp">Show password</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Default Message Template</label>
                        <textarea name="message_template" class="form-control bg-light" rows="8" required><?php echo htmlspecialchars($settings['message_template']); ?></textarea>
                        <div class="form-text mt-2">
                            <strong>Available Variables:</strong>
                            <code>{CustomerName}</code>, <code>{OrderID}</code>, <code>{OrderStatus}</code>, <code>{TrackingID}</code>, <code>{OrderAmount}</code>
                        </div>
                    </div>

                    <!-- ===== WhatsApp Chat Widget Section ===== -->
                    <div class="border-top pt-4 mt-2">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold m-0"><i class="fas fa-comment-dots text-success me-2"></i>Chat Widget (Floating Button)</h5>
                                <small class="text-muted">Controls the WhatsApp chat bubble shown on your storefront.</small>
                            </div>
                            <div class="form-check form-switch fs-5 m-0">
                                <input class="form-check-input" type="checkbox" role="switch" name="chat_widget_enabled" id="enableChatWidget" <?php echo ($settings['chat_widget_enabled'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label ms-2 fs-6 fw-bold" for="enableChatWidget">Enable Widget</label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3 text-start">
                                <label class="form-label fw-bold d-block">Widget Phone Number</label>
                                <?php echo render_phone_input('chat_widget_number', $settings['chat_widget_number'], true); ?>
                                <small class="text-muted">Select country code and enter number.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Pre-fill Message</label>
                                <input type="text" name="chat_widget_message" class="form-control bg-light" placeholder="Hello, I have a question..." value="<?php echo htmlspecialchars($settings['chat_widget_message'] ?? 'Hello, I have a question about your products.'); ?>">
                                <small class="text-muted">Message auto-filled when customer taps the button.</small>
                            </div>
                        </div>
                    </div>
                    <!-- ===== End Chat Widget Section ===== -->

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-success btn-custom px-4"><i class="fas fa-save me-2"></i>Save Configuration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-5">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h5 class="fw-bold m-0"><i class="fas fa-history text-secondary me-2"></i>Recent Message Logs</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="ps-4">Order</th>
                                <th>Sent To</th>
                                <th>Mode</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($logs && $logs->num_rows > 0): ?>
                                <?php while($log = $logs->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <a href="manage_orders.php" class="text-primary fw-bold text-decoration-none">#<?php echo $log['order_number']; ?></a>
                                        <div class="small text-muted"><?php echo htmlspecialchars($log['customer_name']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['customer_number']); ?></td>
                                    <td><span class="badge bg-<?php echo $log['sending_mode'] == 'api' ? 'info' : 'secondary'; ?>"><?php echo strtoupper($log['sending_mode']); ?></span></td>
                                    <td>
                                        <div class="text-success small fw-bold"><i class="fas fa-check-double me-1"></i><?php echo htmlspecialchars($log['status']); ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?php echo date('M d, H:i', strtotime($log['sent_at'])); ?></div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">No WhatsApp messages have been sent yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>
