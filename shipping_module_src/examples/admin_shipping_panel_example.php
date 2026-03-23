<?php
/**
 * admin_shipping_panel_example.php
 * Demonstrates a basic HTML/PHP block that an admin would use 
 * to control shipping via Repository methods securely.
 * 
 * Simply include this file into a back-end page.
 */

use ShippingModule\Config\ShippingConfig;
use ShippingModule\Repositories\ShippingRepository;

// Autoloader fallback
require_once __DIR__ . '/../src/Config/ShippingConfig.php';
require_once __DIR__ . '/../src/Repositories/ShippingRepository.php';

$config = new ShippingConfig();
$db = $config->getConnection();
$repo = new ShippingRepository($db);

// Handle POST save form safely
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_shipping'])) {
    
    // Process checkbox - if unchecked it isn't posted
    $freeShippingEnabled = isset($_POST['free_shipping_enabled']) ? '1' : '0';
    $repo->updateSetting('free_shipping_enabled', $freeShippingEnabled);
    
    // Minimum threshold
    $minFree = floatval($_POST['free_shipping_min_amount']);
    $repo->updateSetting('free_shipping_min_amount', number_format($minFree, 2, '.', ''));
    
    // Update Flat Rate 
    $flatRate = floatval($_POST['default_flat_rate']);
    $repo->updateSetting('default_flat_rate', number_format($flatRate, 2, '.', ''));
    
    // Quick success message
    $successMessage = "Shipping Configuration successfully safely saved to Database!";
}

// Fetch current configurations straight from Repo
$settings = $repo->getSettings();
$freeShippingChecked = ($settings['free_shipping_enabled'] ?? '0') === '1' ? 'checked' : '';
$minAmount = $settings['free_shipping_min_amount'] ?? '1000.00';
$flatRate = $settings['default_flat_rate'] ?? '80.00';

?>

<!-- Basic Admin HTML Demonstration -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0 text-white">Shipping Settings (Example Integration)</h5>
    </div>
    <div class="card-body">
        
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success"><?php echo $successMessage; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php echo csrf_input(); ?>
            <p class="text-muted">Master control switch for checkout delivery costs.</p>

            <!-- Free Shipping Toggle -->
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="freeShippingToggle" name="free_shipping_enabled" value="1" <?php echo $freeShippingChecked; ?>>
                <label class="form-check-label fw-bold" for="freeShippingToggle">Enable Free Shipping Threshold</label>
            </div>

            <!-- Minimum Amount Threshold -->
            <div class="mb-3">
                <label class="form-label text-dark">Minimum Cart Subtotal For Free Shipping (₹)</label>
                <input type="number" step="0.01" class="form-control" name="free_shipping_min_amount" value="<?php echo $minAmount; ?>">
                <small class="text-muted">Users automatically get zero-cost shipping if cart total climbs above this threshold.</small>
            </div>

            <hr>

            <!-- Flat Rate Default -->
            <div class="mb-3">
                <label class="form-label text-dark">Default Standard Flat Rate Shipping (₹)</label>
                <input type="number" step="0.01" class="form-control" name="default_flat_rate" value="<?php echo $flatRate; ?>">
                <small class="text-muted">Applied if the cart total is under the threshold, or if free shipping is entirely off.</small>
            </div>

            <button type="submit" name="save_shipping" class="btn btn-success px-4 mt-2">
                <i class="fas fa-save me-1"></i> Save Configuration
            </button>
        </form>

    </div>
</div>
