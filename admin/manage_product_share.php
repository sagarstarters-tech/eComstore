<?php
require_once 'admin_header.php';

// Fetch current settings
$settings_query = "SELECT * FROM product_share_settings WHERE id = 1";
$result = $conn->query($settings_query);
$settings = $result->fetch_assoc();

if (!$settings) {
    // Failsafe insert if missing
    $conn->query("INSERT IGNORE INTO product_share_settings (id) VALUES (1)");
    $settings = ['whatsapp_status'=>1, 'facebook_status'=>1, 'telegram_status'=>1, 'copylink_status'=>1, 'section_title'=>'Share Product', 'icon_style'=>'rounded'];
}

$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section_title = $conn->real_escape_string($_POST['section_title']);
    $icon_style = $conn->real_escape_string($_POST['icon_style']);
    $whatsapp_status = isset($_POST['whatsapp_status']) ? 1 : 0;
    $facebook_status = isset($_POST['facebook_status']) ? 1 : 0;
    $telegram_status = isset($_POST['telegram_status']) ? 1 : 0;
    $copylink_status = isset($_POST['copylink_status']) ? 1 : 0;

    $update_query = "UPDATE product_share_settings SET 
        section_title = '$section_title',
        icon_style = '$icon_style',
        whatsapp_status = $whatsapp_status,
        facebook_status = $facebook_status,
        telegram_status = $telegram_status,
        copylink_status = $copylink_status
        WHERE id = 1";

    if ($conn->query($update_query)) {
        $success_msg = "Product Share Settings updated successfully.";
        // Refresh settings
        $result = $conn->query($settings_query);
        $settings = $result->fetch_assoc();
    }
}
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="mb-0 text-dark fw-bold"><i class="fas fa-share-alt me-2 text-primary"></i> Product Share Settings</h2>
        <p class="text-muted mb-0">Manage social sharing options for the Single Product Page.</p>
    </div>
</div>

<?php if ($success_msg): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?>
    <button type="button" class="btn-close" data-mdb-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <form method="POST">
    <?php echo csrf_input(); ?>
            <h5 class="fw-bold mb-4 border-bottom pb-2">General Settings</h5>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Share Section Title</label>
                    <input type="text" name="section_title" class="form-control form-control-lg bg-light" 
                           value="<?php echo htmlspecialchars($settings['section_title']); ?>" required>
                    <small class="text-muted">The heading displayed above the share icons on the product page.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Icon Style</label>
                    <select name="icon_style" class="form-select form-select-lg bg-light">
                        <option value="rounded" <?php echo ($settings['icon_style'] == 'rounded') ? 'selected' : ''; ?>>Rounded Corners</option>
                        <option value="circle" <?php echo ($settings['icon_style'] == 'circle') ? 'selected' : ''; ?>>Circle</option>
                        <option value="square" <?php echo ($settings['icon_style'] == 'square') ? 'selected' : ''; ?>>Square</option>
                    </select>
                </div>
            </div>

            <h5 class="fw-bold mb-4 border-bottom pb-2 mt-5">Active Share Platforms</h5>

            <div class="row align-items-center py-2 border-bottom">
                <div class="col-md-6 border-end">
                    <div class="d-flex align-items-center">
                        <i class="fab fa-whatsapp fa-2x text-success me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">WhatsApp</h6>
                            <small class="text-muted">Allow users to share directly via WhatsApp</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input" type="checkbox" name="whatsapp_status" id="whatsappSwitch" value="1" 
                               style="width: 40px; height: 20px;" <?php echo ($settings['whatsapp_status']) ? 'checked' : ''; ?>>
                    </div>
                </div>
            </div>

            <div class="row align-items-center py-2 border-bottom mt-2">
                <div class="col-md-6 border-end">
                    <div class="d-flex align-items-center">
                        <i class="fab fa-facebook fa-2x text-primary me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Facebook</h6>
                            <small class="text-muted">Allow users to post to Facebook</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input" type="checkbox" name="facebook_status" id="fbSwitch" value="1" 
                               style="width: 40px; height: 20px;" <?php echo ($settings['facebook_status']) ? 'checked' : ''; ?>>
                    </div>
                </div>
            </div>

            <div class="row align-items-center py-2 border-bottom mt-2">
                <div class="col-md-6 border-end">
                    <div class="d-flex align-items-center">
                        <i class="fab fa-telegram fa-2x text-info me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Telegram</h6>
                            <small class="text-muted">Allow users to share via Telegram</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input" type="checkbox" name="telegram_status" id="tgSwitch" value="1" 
                               style="width: 40px; height: 20px;" <?php echo ($settings['telegram_status']) ? 'checked' : ''; ?>>
                    </div>
                </div>
            </div>

            <div class="row align-items-center py-2 mt-2">
                <div class="col-md-6 border-end">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-link fa-2x text-secondary me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Copy Link</h6>
                            <small class="text-muted">Allow users to copy the product URL</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input" type="checkbox" name="copylink_status" id="copySwitch" value="1" 
                               style="width: 40px; height: 20px;" <?php echo ($settings['copylink_status']) ? 'checked' : ''; ?>>
                    </div>
                </div>
            </div>

            <div class="mt-5 text-end">
                <button type="submit" class="btn btn-primary btn-lg px-5">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>
