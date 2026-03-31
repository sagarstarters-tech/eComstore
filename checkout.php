<?php
ob_start();
include_once __DIR__ . '/includes/session_setup.php';
include 'includes/db_connect.php';
require_once 'includes/mail_functions.php';

require_once 'shipping_module_src/src/Config/ShippingConfig.php';
require_once 'shipping_module_src/src/Repositories/ShippingRepository.php';
require_once 'shipping_module_src/src/Services/ShippingService.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user/login.php");
    exit;
}

if (empty($_SESSION['cart']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cart.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$cart_items = [];
$subtotal = 0;
if (!empty($_SESSION['cart'])) {
    // Sanitize IDs for the IN clause
    $safe_ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    $result = $conn->query("SELECT * FROM products WHERE id IN ($safe_ids)");

    $is_all_digital = true;
    $cod_allowed_for_all = true;
    while ($row = $result->fetch_assoc()) {
        $qty = (int)$_SESSION['cart'][$row['id']];
        // Enforce stock limit at checkout to prevent race conditions
        if ($qty > $row['stock']) {
            $qty = $row['stock'];
            $_SESSION['cart'][$row['id']] = $qty; // Update cart
        }
        
        if ($qty > 0) {
            $total = (float)$row['price'] * $qty;
            $subtotal += $total;
            $row['qty'] = $qty;
            $cart_items[] = $row;
            
            if ($row['product_type'] === 'physical') {
                $is_all_digital = false;
            }

            if ($row['cod_available'] == 0) {
                $cod_allowed_for_all = false;
            }
        } else {
            unset($_SESSION['cart'][$row['id']]); // Remove out of stock items
        }
    }
}

$shippingConfig = new \ShippingModule\Config\ShippingConfig();
$shippingRepo = new \ShippingModule\Repositories\ShippingRepository($shippingConfig->getConnection());
$shippingService = new \ShippingModule\Services\ShippingService($shippingRepo);

if (isset($is_all_digital) && $is_all_digital) {
    $shipping_cost = 0;
    $grand_total = $subtotal;
    $shippingCalc = ['shipping_metadata' => ['is_free' => true, 'message' => 'Digital products - No shipping required']];
} else {
    $shippingCalc = $shippingService->getFinalOrderTotals($subtotal, $cart_items);
    $shipping_cost = $shippingCalc['shipping_cost'];
    $grand_total = $shippingCalc['grand_total'];
}

// ── Partial COD Config ───────────────────────────────────────────────────────
$cod_enabled_global     = isset($global_settings['cod_enabled']) && $global_settings['cod_enabled'] == '1';
$cod_advance_enabled    = isset($global_settings['cod_advance_enabled']) && $global_settings['cod_advance_enabled'] == '1';
$cod_advance_pct        = max(1, min(99, (float)($global_settings['cod_advance_percentage'] ?? 30)));
$cod_advance_min_order  = (float)($global_settings['cod_advance_min_order'] ?? 0);

$is_partial_cod = $cod_enabled_global
               && $cod_advance_enabled
               && $cod_allowed_for_all
               && ($cod_advance_min_order == 0 || $grand_total >= $cod_advance_min_order);

$advance_amount   = $is_partial_cod ? round($grand_total * $cod_advance_pct / 100, 2) : 0;
$remaining_amount = $is_partial_cod ? round($grand_total - $advance_amount, 2) : 0;
// ────────────────────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Security check failed. Please submit the form again.";
        header("Location: checkout.php");
        exit;
    }
    
    // Start Transaction for Order Integrity
    $conn->begin_transaction();
    try {
        $status = 'pending';
        $payment_method = $_POST['payment_method'] ?? 'cod';
        
        // Determine payment_mode for Partial COD
        $payment_mode = null;
        if ($payment_method === 'cod' && $is_partial_cod) {
            $payment_mode = 'COD_PARTIAL';
        }

        // 1. Insert order
        // Try with partial COD columns first; fall back to basic INSERT if they don't exist
        if ($is_partial_cod) {
            $stmt = $conn->prepare(
                "INSERT INTO orders (user_id, total_amount, payment_method, status, advance_amount, remaining_amount, payment_mode)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            if ($stmt) {
                $stmt->bind_param("idssdds", $user_id, $grand_total, $payment_method, $status, $advance_amount, $remaining_amount, $payment_mode);
            } else {
                // Partial COD columns may not exist — fall back to basic insert and disable partial COD for this order
                $is_partial_cod = false;
                $stmt = $conn->prepare(
                    "INSERT INTO orders (user_id, total_amount, payment_method, status)
                     VALUES (?, ?, ?, ?)"
                );
                if (!$stmt) throw new Exception("Database error: " . $conn->error);
                $stmt->bind_param("idss", $user_id, $grand_total, $payment_method, $status);
            }
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO orders (user_id, total_amount, payment_method, status)
                 VALUES (?, ?, ?, ?)"
            );
            if (!$stmt) throw new Exception("Database error: " . $conn->error);
            $stmt->bind_param("idss", $user_id, $grand_total, $payment_method, $status);
        }
        if (!$stmt->execute()) throw new Exception("Failed to create order: " . $stmt->error);
        $order_id = $stmt->insert_id;
        $stmt->close();
        
        // 2. Insert order items and update stock
        $stmt2 = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, shipping_cost) VALUES (?, ?, ?, ?, ?)");
        $stock_stmt = $conn->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ? AND stock >= ?");
        
        foreach ($cart_items as $item) {
            $item_shipping = ($item['product_type'] === 'physical') ? (float)$item['shipping_cost'] : 0.00;
            $qty = (int)$item['qty'];
            $p_id = (int)$item['id'];
            
            $stmt2->bind_param("iiidd", $order_id, $p_id, $qty, $item['price'], $item_shipping);
            if (!$stmt2->execute()) throw new Exception("Failed to insert order item.");

            // Update stock with safety check (ensure stock hasn't dropped since start of request)
            $stock_stmt->bind_param("iii", $qty, $p_id, $qty);
            $stock_stmt->execute();
            if ($stock_stmt->affected_rows === 0) {
                if ($item['product_type'] === 'physical') {
                    throw new Exception("Product '{$item['name']}' is no longer in stock.");
                }
            }
        }
        $stmt2->close();
        $stock_stmt->close();
        
        // If we reach here, commit everything
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Checkout failed: " . $e->getMessage();
        header("Location: cart.php");
        exit;
    }
    
    if ($payment_method === 'cod') {
        if ($is_partial_cod) {
            // ── Partial COD: Redirect to PhonePe for advance amount ──────
            $_SESSION['cod_partial_order'] = true;
            $payment_amount = $advance_amount; // override amount for phonepe_payment.php
            include 'phonepe_payment.php';
            exit;
        } else {
            // ── Standard COD ─────────────────────────────────────────────
            unset($_SESSION['cart']);
            $customer_email = $user_data['email'];
            $customer_name = $user_data['name'];
            sendOrderConfirmationEmail($conn, $order_id, $customer_email, $customer_name, $cart_items, $grand_total, $global_currency, $payment_method);
            
            require_once 'tracking_module_src/src/Config/TrackingConfig.php';
            require_once 'tracking_module_src/src/Repositories/TrackingRepository.php';
            $trackingConfig = new \TrackingModule\Config\TrackingConfig();
            $trackingRepo = new \TrackingModule\Repositories\TrackingRepository($trackingConfig->getConnection());
            $trackingRepo->logStatusChange($order_id, 'pending', 'Order placed successfully.', 'system');

            require_once 'includes/digital_product_functions.php';
            activateDigitalDownloads($conn, $order_id);

            $success = "Order placed successfully! Order ID: #$order_id";
        }
    } elseif ($payment_method === 'phonepe') {
        include 'phonepe_payment.php';
        exit;
    }
}

// Now include header (no output sent before this except if redirected)
include 'includes/header.php';
?>

<div class="container mt-5 pt-3 mb-5">
    <h1 class="montserrat fw-bold primary-blue mb-4">Checkout</h1>
    
    <?php if(isset($success)): ?>
        <div class="alert alert-success text-center py-4 bg-light border-success">
            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
            <h3><?php echo $success; ?></h3>
            <p>Thank you for your purchase. We are processing your order.</p>
            <a href="user/orders.php" class="btn btn-primary btn-custom mt-3">View My Orders</a>
        </div>
    <?php else: ?>
    <div class="row">
        <!-- Billing Details Form -->
        <div class="col-lg-8 mb-4">
            <div class="card product-card border-0 shadow-sm p-4">
                <h4 class="mb-4 fw-bold">Billing Details</h4>
                <form method="POST" id="checkoutForm">
                    <?php echo csrf_field(); ?>
                    <?php if ($is_all_digital): ?>
                        <div class="alert alert-info py-3 mb-4 rounded-3 border-0 bg-info bg-opacity-10 text-info">
                            <i class="fas fa-info-circle me-2"></i> This order contains only digital products. Shipping address is not required.
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="billing_name" class="form-control" value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>" required>
                    </div>

                    <div id="address_fields" class="<?php echo $is_all_digital ? 'd-none' : ''; ?>">
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="billing_address" class="form-control" value="<?php echo htmlspecialchars($user_data['address'] ?? ''); ?>" <?php echo $is_all_digital ? '' : 'required'; ?>>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="billing_city" class="form-control" value="<?php echo htmlspecialchars($user_data['city'] ?? ''); ?>" <?php echo $is_all_digital ? '' : 'required'; ?>>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">State/Province</label>
                                <input type="text" name="billing_state" class="form-control" value="<?php echo htmlspecialchars($user_data['state'] ?? ''); ?>" <?php echo $is_all_digital ? '' : 'required'; ?>>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Country</label>
                                <input type="text" name="billing_country" class="form-control" value="<?php echo htmlspecialchars($user_data['country'] ?? ''); ?>" <?php echo $is_all_digital ? '' : 'required'; ?>>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Zip Code</label>
                                <input type="text" name="billing_zip" class="form-control" value="<?php echo htmlspecialchars($user_data['zip_code'] ?? ''); ?>" <?php echo $is_all_digital ? '' : 'required'; ?>>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <?php echo render_phone_input('billing_phone', $user_data['phone'] ?? '', true); ?>
                    </div>

                    
                    <hr class="my-4">
                    <h4 class="mb-3 fw-bold">Payment Method</h4>
                    
                    <?php 
                        $phonepe_enabled = isset($global_settings['phonepe_enabled']) && $global_settings['phonepe_enabled'] == '1';
                        $cod_enabled = isset($global_settings['cod_enabled']) && $global_settings['cod_enabled'] == '1';
                    ?>
                    
                    <?php if ($phonepe_enabled): ?>
                    <div class="mb-3">
                        <div class="form-check border rounded p-3 bg-light">
                            <input class="form-check-input ms-1" type="radio" name="payment_method" id="pay_phonepe" value="phonepe" required <?php echo !$cod_enabled ? 'checked' : ''; ?> onchange="updatePaymentUI()">
                            <label class="form-check-label ms-2 fw-bold" for="pay_phonepe">
                                Pay Online via PhonePe
                            </label>
                            <small class="d-block text-success mt-1 ms-2"><i class="fas fa-shield-alt me-1"></i> Secure payment via UPI, Cards, NetBanking.</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($cod_enabled): ?>
                        <?php if ($cod_allowed_for_all): ?>
                        <div class="mb-3">
                            <div class="form-check border rounded p-3 bg-light">
                                <input class="form-check-input ms-1" type="radio" name="payment_method" id="pay_cod" value="cod" required <?php echo !$phonepe_enabled ? 'checked' : ''; ?> onchange="updatePaymentUI()">
                                <label class="form-check-label ms-2 fw-bold" for="pay_cod">
                                    Cash On Delivery (COD)
                                    <?php if ($is_partial_cod): ?>
                                        <span class="badge bg-warning text-dark ms-2 small fw-semibold">Advance Required</span>
                                    <?php endif; ?>
                                </label>
                                <small class="d-block text-success mt-1 ms-2"><i class="fas fa-check-circle me-1"></i> Pay with cash upon delivery.</small>
                            </div>
                        </div>

                        <?php if ($is_partial_cod): ?>
                        <!-- ── Partial COD Info Box ──────────────────────────────── -->
                        <div id="partial_cod_info" class="mb-4" style="display:none;">
                            <div class="border border-warning rounded-3 p-4" style="background: linear-gradient(135deg, #fffbf0 0%, #fff8e1 100%);">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-mobile-alt fa-lg text-warning me-2"></i>
                                    <h6 class="fw-bold mb-0 text-dark">Advance Payment Required</h6>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                            <span class="text-muted small fw-semibold">Order Total</span>
                                            <span class="fw-bold"><?php echo $global_currency; ?><?php echo number_format($grand_total, 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                            <span class="text-muted small fw-semibold">
                                                <i class="fas fa-mobile-alt me-1 text-primary"></i>
                                                Advance via PhonePe (<?php echo $cod_advance_pct; ?>%)
                                            </span>
                                            <span class="fw-bold text-primary"><?php echo $global_currency; ?><?php echo number_format($advance_amount, 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted small fw-semibold">
                                                <i class="fas fa-truck me-1 text-success"></i>
                                                Remaining COD (on delivery)
                                            </span>
                                            <span class="fw-bold text-success"><?php echo $global_currency; ?><?php echo number_format($remaining_amount, 2); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-warning py-2 mb-0 small fw-semibold border-0 bg-warning bg-opacity-25 rounded-2">
                                    <i class="fas fa-info-circle me-1"></i>
                                    You need to pay <strong><?php echo $global_currency; ?><?php echo number_format($advance_amount, 2); ?></strong> in advance via PhonePe to confirm your COD order. The remaining <strong><?php echo $global_currency; ?><?php echo number_format($remaining_amount, 2); ?></strong> will be collected at delivery.
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php else: ?>
                        <div class="mb-4">
                            <div class="alert alert-warning py-3 border-0 border-start border-warning border-4">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>COD Not Available:</strong> Cash on Delivery is not available for one or more items in your cart.
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    
                    <?php if (!$phonepe_enabled && !$cod_enabled): ?>
                        <div class="alert alert-danger px-4 py-3 border-0 border-start border-danger border-4 fw-bold">No payment methods are currently available. Please contact support.</div>
                    <?php else: ?>
                        <button type="submit" id="placeOrderBtn" class="btn btn-primary btn-lg btn-custom w-100 mt-4 py-3">Place Order</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card product-card border-0 shadow-sm">
                <div class="card-body p-4 bg-light">
                    <h5 class="fw-bold mb-4">Your Order</h5>
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach($cart_items as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 border-bottom border-light">
                            <div>
                                <h6 class="my-0 text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <small class="text-muted">Qty: <?php echo $item['qty']; ?></small>
                            </div>
                            <span class="text-muted"><?php echo $global_currency; ?><?php echo number_format($item['qty'] * $item['price'], 2); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Cart Subtotal</span>
                        <span><?php echo $global_currency; ?><?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Shipping Cost</span>
                        <span class="<?php echo $shippingCalc['shipping_metadata']['is_free'] ? 'text-success fw-bold' : ''; ?>">
                            <?php echo $shippingCalc['shipping_metadata']['is_free'] ? 'Free' : '+ ' . $global_currency . number_format($shipping_cost, 2); ?>
                        </span>
                    </div>
                    <div class="alert <?php echo $shippingCalc['shipping_metadata']['is_free'] ? 'alert-success' : 'alert-info'; ?> p-2 small mt-2 mb-3 text-center">
                        <?php echo htmlspecialchars($shippingCalc['shipping_metadata']['message']); ?>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5 mt-3">
                        <span>Total Amount</span>
                        <strong class="primary-blue"><?php echo $global_currency; ?><?php echo number_format($grand_total, 2); ?></strong>
                    </div>

                    <?php if ($is_partial_cod): ?>
                    <!-- Partial COD summary lines (always visible in sidebar) -->
                    <div id="partial_cod_sidebar" class="mt-3 pt-3 border-top" style="display:none;">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted"><i class="fas fa-mobile-alt me-1"></i>Advance (PhonePe)</span>
                            <span class="fw-bold text-primary"><?php echo $global_currency; ?><?php echo number_format($advance_amount, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted"><i class="fas fa-truck me-1"></i>Pay on Delivery</span>
                            <span class="fw-bold text-success"><?php echo $global_currency; ?><?php echo number_format($remaining_amount, 2); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($is_partial_cod): ?>
<script>
function updatePaymentUI() {
    var codRadio    = document.getElementById('pay_cod');
    var partialInfo = document.getElementById('partial_cod_info');
    var sidebarInfo = document.getElementById('partial_cod_sidebar');
    var placeBtn    = document.getElementById('placeOrderBtn');

    if (!codRadio) return;

    var isCodSelected = codRadio.checked;

    if (partialInfo) partialInfo.style.display = isCodSelected ? '' : 'none';
    if (sidebarInfo) sidebarInfo.style.display = isCodSelected ? '' : 'none';
    if (placeBtn) {
        placeBtn.innerHTML = isCodSelected
            ? '<i class="fas fa-mobile-alt me-2"></i>Pay Advance &amp; Confirm COD Order'
            : '<i class="fas fa-lock me-2"></i>Place Order';
    }
}

// Run on page load to set initial state
document.addEventListener('DOMContentLoaded', function() {
    updatePaymentUI();
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
