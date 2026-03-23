<?php
/**
 * cart_integration.php
 * Demonstrates how to securely integrate the backend shipping logic 
 * on your front-end PHP pages before any AJAX even runs.
 * 
 * Includes the native JS listener to make it dynamic.
 */

use ShippingModule\Config\ShippingConfig;
use ShippingModule\Repositories\ShippingRepository;
use ShippingModule\Services\ShippingService;

require_once __DIR__ . '/../src/Config/ShippingConfig.php';
require_once __DIR__ . '/../src/Repositories/ShippingRepository.php';
require_once __DIR__ . '/../src/Services/ShippingService.php';

// Instantiate Core Logic
$config = new ShippingConfig();
$db = $config->getConnection();
$repo = new ShippingRepository($db);
$shippingService = new ShippingService($repo);

// Example Cart Total (Normally this comes from your session e.g array_sum($_SESSION['cart']))
$myCartTotal = 850.50; 

// Initial server-side load 
$totalsDisplay = $shippingService->getFinalOrderTotals($myCartTotal);
$shippingMeta = $totalsDisplay['shipping_metadata'];

$isEligible = $shippingMeta['is_free'];
$shippingMessage = $shippingMeta['message'];
$shippingCost = $totalsDisplay['shipping_cost'];
$grandTotal = $totalsDisplay['grand_total'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cart Payment Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">

            <!-- Cart Summary Block -->
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Order Summary</h5>
                    
                    <!-- We store the raw data value in a data attribute to interact with the JS cleanly -->
                    <div class="d-flex justify-content-between mb-3 text-muted">
                        <span>Cart Subtotal</span>
                        <span id="base_cart_total" data-value="<?php echo $myCartTotal; ?>">
                            ₹<?php echo number_format($myCartTotal, 2); ?>
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3 text-muted">
                        <span>Shipping Cost</span>
                        <span id="shipping_cost_display" class="<?php echo $isEligible ? 'text-success fw-bold' : ''; ?>">
                            <?php echo $isEligible ? 'Free' : '₹' . number_format($shippingCost, 2); ?>
                        </span>
                    </div>

                    <div class="d-flex justify-content-between mb-3 text-muted">
                        <span>Discount</span>
                        <span>₹0.00</span>
                    </div>
                    
                    <hr>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-bold fs-5">Final Amount</span>
                        <span id="grand_total_display" class="fw-bold fs-5 text-dark">
                            ₹<?php echo number_format($grandTotal, 2); ?>
                        </span>
                    </div>

                    <!-- Dynamic Message (E.g "Add ₹X more for free shipping") -->
                    <div id="shipping_message_prompt" class="alert <?php echo $isEligible ? 'alert-success' : 'alert-info'; ?> p-2 small mt-3 text-center mb-0">
                        <?php echo htmlspecialchars($shippingMessage); ?>
                    </div>
                    
                    <button class="btn btn-primary w-100 mt-4 rounded-3 py-2 fw-bold shadow-sm">Proceed to Checkout</button>

                </div>
            </div>

            <!-- Example Simulator Buttons to manually test the AJAX hooks -->
            <div class="mt-4 text-center">
                <p class="text-muted small">Simulate Item Update:</p>
                <button class="btn btn-sm btn-outline-secondary" onclick="simulateCartChange(250.00)">Set Cart slightly below Free (₹250)</button>
                <button class="btn btn-sm btn-outline-success mt-2" onclick="simulateCartChange(1200.00)">Set Cart generously above Free (₹1200)</button>
            </div>

        </div>
    </div>
</div>

<!-- Load Example JS hooks -->
<script src="ajax_update_shipping.js"></script>
<script>
    // Simulator helper purely for this demo page
    function simulateCartChange(newAmount) {
        // Change DOM dataset
        const cartTotalNode = document.getElementById('base_cart_total');
        cartTotalNode.dataset.value = newAmount;
        cartTotalNode.innerText = '₹' + newAmount.toFixed(2);
        
        // Dispatch custom global event representing cart update
        document.dispatchEvent(new CustomEvent('cart_updated', {
            detail: {
                cart_total: newAmount
            }
        }));
    }
</script>
</body>
</html>
