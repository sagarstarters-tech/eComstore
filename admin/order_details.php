<?php
include 'admin_header.php';

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    echo "<div class='alert alert-danger'>Invalid Order ID</div>";
    include 'admin_footer.php';
    exit;
}

// Fetch order info
$order_q = $conn->query("
    SELECT o.*, 
           u.name as user_name, u.email as user_email, u.phone as user_phone,
           u.address as user_address, u.city as user_city, u.state as user_state, 
           u.country as user_country, u.zip_code as user_zip
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = $order_id
");
if (!$order_q || $order_q->num_rows === 0) {
    echo "<div class='alert alert-danger'>Order not found</div>";
    include 'admin_footer.php';
    exit;
}

$order = $order_q->fetch_assoc();

// Fetch order items
$items_q = $conn->query("
    SELECT oi.*, p.name as product_name, p.image as product_image, p.product_type 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = $order_id
");


// Include tracking module logic
require_once __DIR__ . '/../tracking_module_src/src/Config/TrackingConfig.php';
require_once __DIR__ . '/../tracking_module_src/src/Repositories/TrackingRepository.php';
require_once __DIR__ . '/../tracking_module_src/src/Services/TrackingService.php';

$trackingConfig = new \TrackingModule\Config\TrackingConfig();
$trackingRepo = new \TrackingModule\Repositories\TrackingRepository($trackingConfig->getConnection());
$trackingService = new \TrackingModule\Services\TrackingService($trackingRepo);

$trackingData = $trackingService->getAdminTracking($order_id);
$info = $trackingData['order_info'];
$shipping = $trackingData['shipping'];
$timeline = $trackingData['timeline'];

$stages = ['Pending', 'Processing', 'Shipped', 'Out for Delivery', 'Delivered'];
$stageIndex = $info['progress_stage_index'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Order Details - #<?php echo $order_id; ?></h4>
    <div class="d-flex gap-2">
        <a href="manage_order_tracking.php?id=<?php echo $order_id; ?>" class="btn btn-info btn-custom text-white px-3"><i class="fas fa-truck-fast me-2"></i>Manage Tracking</a>
        <a href="manage_orders.php" class="btn btn-light btn-custom border"><i class="fas fa-arrow-left me-2"></i>Back to Orders</a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="fw-bold mb-0">Order Items</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Shipping</th>
                                <th class="pe-4 text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_item_price = 0;
                            $total_shipping = 0;
                            while($item = $items_q->fetch_assoc()): 
                                $item_total = $item['quantity'] * $item['price'];
                                $total_item_price += $item_total;
                                
                                $item_shipping = 0;
                                if ($item['product_type'] === 'physical') {
                                    $item_shipping = $item['shipping_cost'] * $item['quantity'];
                                }
                                $total_shipping += $item_shipping;
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="../assets/images/<?php echo $item['product_image']; ?>" class="rounded me-3" style="width: 50px; height: 50px; object-fit: contain; background-color: #fff; padding: 5px;" alt="">
                                        <div>
                                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                            <small class="text-muted">ID: #<?php echo $item['product_id']; ?></small>
                                            <?php if ($item['product_type'] !== 'physical'): ?>
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info ms-1"><?php echo ucfirst($item['product_type']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo $global_currency; ?><?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <?php if($item['product_type'] !== 'physical'): ?>
                                        <span class="text-muted small">N/A</span>
                                    <?php elseif($item['shipping_cost'] > 0): ?>
                                        <?php echo $global_currency; ?><?php echo number_format($item['shipping_cost'], 2); ?>
                                    <?php else: ?>
                                        <span class="text-success small">Free</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end fw-bold"><?php echo $global_currency; ?><?php echo number_format($item_total + $item_shipping, 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light border-0 p-4">
                <div class="row justify-content-end">
                    <div class="col-md-5 col-lg-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal (Items)</span>
                            <span class="fw-bold"><?php echo $global_currency; ?><?php echo number_format($total_item_price, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Shipping Total</span>
                            <span class="fw-bold"><?php echo $total_shipping > 0 ? $global_currency . number_format($total_shipping, 2) : '<span class="text-success">Free</span>'; ?></span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Order Total</span>
                            <span class="text-primary"><?php echo $global_currency; ?><?php echo number_format($total_item_price + $total_shipping, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h5 class="fw-bold mb-3">Customer Information</h5>
            <div class="mb-3">
                <p class="text-muted mb-0 small uppercase fw-bold">Name</p>
                <h6 class="fw-bold"><?php echo htmlspecialchars($order['user_name'] ?? 'Unknown User'); ?></h6>
            </div>
            <div class="mb-3">
                <p class="text-muted mb-0 small uppercase fw-bold">Email</p>
                <h6 class="fw-bold"><?php echo htmlspecialchars($order['user_email'] ?? 'N/A'); ?></h6>
            </div>
            <div class="mb-3">
                <p class="text-muted mb-0 small uppercase fw-bold">Phone</p>
                <h6 class="fw-bold"><?php echo htmlspecialchars($order['user_phone'] ?? 'N/A'); ?></h6>
            </div>
            <hr>
            <h5 class="fw-bold mb-3">Shipping Address</h5>
            <div class="mb-3">
                <p class="text-muted mb-0 small uppercase fw-bold">Address</p>
                <h6 class="fw-bold"><?php echo htmlspecialchars($order['user_address'] ?? 'N/A'); ?></h6>
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <p class="text-muted mb-0 small uppercase fw-bold">City</p>
                    <h6 class="fw-bold"><?php echo htmlspecialchars($order['user_city'] ?? 'N/A'); ?></h6>
                </div>
                <div class="col-6 mb-3">
                    <p class="text-muted mb-0 small uppercase fw-bold">Zip Code</p>
                    <h6 class="fw-bold"><?php echo htmlspecialchars($order['user_zip'] ?? 'N/A'); ?></h6>
                </div>
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <p class="text-muted mb-0 small uppercase fw-bold">State</p>
                    <h6 class="fw-bold"><?php echo htmlspecialchars($order['user_state'] ?? 'N/A'); ?></h6>
                </div>
                <div class="col-6 mb-3">
                    <p class="text-muted mb-0 small uppercase fw-bold">Country</p>
                    <h6 class="fw-bold"><?php echo htmlspecialchars($order['user_country'] ?? 'N/A'); ?></h6>
                </div>
            </div>
            <hr>
            <div class="mb-3">
                <p class="text-muted mb-0 small uppercase fw-bold">Payment Method</p>
                <h6 class="fw-bold"><?php echo strtoupper($order['payment_method']); ?></h6>
            </div>
            <div class="mb-0">
                <p class="text-muted mb-0 small uppercase fw-bold">Order Date</p>
                <h6 class="fw-bold"><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></h6>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h5 class="fw-bold mb-3">Order Status</h5>
            <form method="POST" action="manage_orders.php">
    <?php echo csrf_input(); ?>
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" value="<?php echo $order_id; ?>">
                <select name="status" class="form-select mb-3" onchange="this.form.submit()">
                    <option value="pending" <?php echo $order['status']=='pending'?'selected':''; ?>>Pending</option>
                    <option value="processing" <?php echo $order['status']=='processing'?'selected':''; ?>>Processing</option>
                    <option value="shipped" <?php echo $order['status']=='shipped'?'selected':''; ?>>Shipped</option>
                    <option value="delivered" <?php echo $order['status']=='delivered'?'selected':''; ?>>Delivered</option>
                    <option value="completed" <?php echo $order['status']=='completed'?'selected':''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $order['status']=='cancelled'?'selected':''; ?>>Cancelled</option>
                </select>
            </form>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
