<?php
include 'admin_header.php';

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Select Order to Track</h4>
        <a href="manage_orders.php" class="btn btn-light btn-custom border"><i class="fas fa-list me-2"></i>All Orders</a>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-5 text-center">
            <div class="mb-4">
                <i class="fas fa-search-location fa-4x text-muted opacity-50"></i>
            </div>
            <h5 class="fw-bold text-dark mb-3">Please provide an Order ID</h5>
            <p class="text-muted mb-4 mx-auto" style="max-width: 400px;">
                Enter a specific Order ID below to manage its tracking information and delivery status.
            </p>
            
            <form action="manage_order_tracking.php" method="GET" class="d-flex justify-content-center mx-auto" style="max-width: 400px;">
    <?php echo csrf_input(); ?>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-hashtag text-muted"></i></span>
                    <input type="number" name="id" class="form-control border-start-0 ps-0" placeholder="Order ID..." required>
                    <button class="btn btn-primary px-4 fw-bold shadow-none" type="submit">Track Order</button>
                </div>
            </form>
            
            <div class="mt-4 pt-3">
                <p class="small text-muted mb-0">Alternatively, you can select an order directly from the <a href="manage_orders.php" class="text-decoration-underline text-primary">Manage Orders</a> page.</p>
            </div>
        </div>
    </div>
    <?php
    include 'admin_footer.php';
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Update Tracking - Order #<?php echo $order_id; ?></h4>
    <a href="manage_orders.php" class="btn btn-light btn-custom border"><i class="fas fa-arrow-left me-2"></i>Back to Orders</a>
</div>

<?php 
// Inject the modular tracking panel
include __DIR__ . '/../tracking_module_src/examples/admin_tracking_panel.php'; 
?>

<?php include 'admin_footer.php'; ?>
