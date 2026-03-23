<?php
include 'admin_header.php';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $layout = $conn->real_escape_string($_POST['layout']);
    $desktop_height = $conn->real_escape_string($_POST['desktop_height']);
    $mobile_height = $conn->real_escape_string($_POST['mobile_height']);
    $show_arrows = isset($_POST['show_arrows']) ? 1 : 0;
    $show_dots = isset($_POST['show_dots']) ? 1 : 0;
    $arrow_style = $conn->real_escape_string($_POST['arrow_style']);
    $dot_style = $conn->real_escape_string($_POST['dot_style']);
    $autoplay = isset($_POST['autoplay']) ? 1 : 0;
    $autoplay_delay = intval($_POST['autoplay_delay']);
    $transition_type = $conn->real_escape_string($_POST['transition_type']);
    $transition_speed = intval($_POST['transition_speed']);

    $sql = "UPDATE hero_slider_settings SET 
            is_active=$is_active, 
            layout='$layout', 
            desktop_height='$desktop_height', 
            mobile_height='$mobile_height', 
            show_arrows=$show_arrows, 
            show_dots=$show_dots, 
            arrow_style='$arrow_style', 
            dot_style='$dot_style', 
            autoplay=$autoplay, 
            autoplay_delay=$autoplay_delay, 
            transition_type='$transition_type', 
            transition_speed=$transition_speed 
            WHERE id=1";

    if ($conn->query($sql)) {
        $success = "Slider settings updated successfully!";
    } else {
        $error = "Error updating settings: " . $conn->error;
    }
}

// Fetch current settings
$settings_q = $conn->query("SELECT * FROM hero_slider_settings LIMIT 1");
$settings = $settings_q->fetch_assoc();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 mb-0 text-gray-800"><i class="fas fa-sliders-h me-2"></i>Hero Slider Settings</h2>
    <div>
        <a href="manage-slides.php" class="btn btn-secondary btn-custom"><i class="fas fa-images me-2"></i>Manage Slides</a>
    </div>
</div>

<?php if(isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
        <button type="button" class="btn-close" data-mdb-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-mdb-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form method="POST" action="">
    <?php echo csrf_input(); ?>
            <input type="hidden" name="action" value="update_settings">
            
            <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">General Settings</h5>
            
            <div class="row mb-4">
                <div class="col-md-12 mb-3">
                    <div class="form-check form-switch form-switch-lg mt-2 px-0 d-flex align-items-center">
                        <input class="form-check-input ms-0 me-3 mt-0" type="checkbox" name="is_active" id="is_active" style="height: 25px; width: 50px;" <?php echo $settings['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="is_active">Enable Hero Slider on Homepage</label>
                    </div>
                </div>
            </div>

            <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">Layout & Dimensions</h5>

            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Layout Style</label>
                    <select name="layout" class="form-select bg-light">
                        <option value="full" <?php echo ($settings['layout'] == 'full') ? 'selected' : ''; ?>>Full Width (Edge to Edge)</option>
                        <option value="boxed" <?php echo ($settings['layout'] == 'boxed') ? 'selected' : ''; ?>>Boxed (Container Width)</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Desktop Height</label>
                    <input type="text" name="desktop_height" class="form-control bg-light" value="<?php echo htmlspecialchars($settings['desktop_height']); ?>" placeholder="e.g. 600px or 80vh">
                    <div class="form-text">Supports px, vh, or % (e.g. 100vh for full screen)</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Mobile Height</label>
                    <input type="text" name="mobile_height" class="form-control bg-light" value="<?php echo htmlspecialchars($settings['mobile_height']); ?>" placeholder="e.g. 400px or 60vh">
                    <div class="form-text">Adjusted height for mobile screens</div>
                </div>
            </div>

            <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">Navigation & Controls</h5>

            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="show_arrows" id="show_arrows" <?php echo $settings['show_arrows'] ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="show_arrows">Show Prev/Next Arrows</label>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Arrow Style</label>
                    <select name="arrow_style" class="form-select bg-light">
                        <option value="light" <?php echo ($settings['arrow_style'] == 'light') ? 'selected' : ''; ?>>Light</option>
                        <option value="dark" <?php echo ($settings['arrow_style'] == 'dark') ? 'selected' : ''; ?>>Dark</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="show_dots" id="show_dots" <?php echo $settings['show_dots'] ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="show_dots">Show Pagination Dots</label>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Dots Style</label>
                    <select name="dot_style" class="form-select bg-light">
                        <option value="light" <?php echo ($settings['dot_style'] == 'light') ? 'selected' : ''; ?>>Light</option>
                        <option value="dark" <?php echo ($settings['dot_style'] == 'dark') ? 'selected' : ''; ?>>Dark</option>
                    </select>
                </div>
            </div>

            <h5 class="fw-bold mb-3 border-bottom pb-2 text-primary">Autoplay & Transitions</h5>

            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="form-check form-switch mt-2 d-flex align-items-center">
                        <input class="form-check-input me-2 mt-0" type="checkbox" name="autoplay" id="autoplay" <?php echo $settings['autoplay'] ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="autoplay">Auto Play Slides</label>
                    </div>
                    <div class="form-text">Slides will pause on hover automatically</div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Autoplay Delay (ms)</label>
                    <input type="number" name="autoplay_delay" class="form-control bg-light" value="<?php echo intval($settings['autoplay_delay']); ?>" min="1000">
                    <div class="form-text">1000ms = 1 second</div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Transition Type</label>
                    <select name="transition_type" class="form-select bg-light">
                        <option value="slide" <?php echo ($settings['transition_type'] == 'slide') ? 'selected' : ''; ?>>Slide Horizontal</option>
                        <option value="fade" <?php echo ($settings['transition_type'] == 'fade') ? 'selected' : ''; ?>>Fade</option>
                        <option value="zoom" <?php echo ($settings['transition_type'] == 'zoom') ? 'selected' : ''; ?>>Zoom</option>
                        <option value="zoom-in" <?php echo ($settings['transition_type'] == 'zoom-in') ? 'selected' : ''; ?>>Zoom In</option>
                        <option value="zoom-out" <?php echo ($settings['transition_type'] == 'zoom-out') ? 'selected' : ''; ?>>Zoom Out</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-bold">Transition Duration (ms)</label>
                    <input type="number" name="transition_speed" class="form-control bg-light" value="<?php echo intval($settings['transition_speed']); ?>" min="100">
                    <div class="form-text">Speed of the animation itself</div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top text-end">
                <button type="submit" class="btn btn-primary btn-custom btn-lg px-5"><i class="fas fa-save me-2"></i>Save Settings</button>
            </div>
        </form>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
