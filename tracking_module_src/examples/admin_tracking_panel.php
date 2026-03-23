<?php
/**
 * admin_tracking_panel.php
 * Demonstrates a block that can be included in `admin/manage_orders.php`
 * to let admins update tracking information.
 */

// We assume $order_id exists in the context where this is included.
// It will query the module cleanly using the endpoint or service.

require_once __DIR__ . '/../src/Config/TrackingConfig.php';
require_once __DIR__ . '/../src/Repositories/TrackingRepository.php';

$config = new \TrackingModule\Config\TrackingConfig();
$repo = new \TrackingModule\Repositories\TrackingRepository($config->getConnection());

$active_couriers = $repo->getActiveCouriers();
$currentTracking = $repo->getTrackingDetailsByOrder($order_id); 
$history = $repo->getOrderStatusHistory($order_id);
?>

<div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-header bg-dark text-white pt-3 pb-2 rounded-top-4">
        <h5 class="fw-bold m-0"><i class="fas fa-truck-fast me-2"></i>Shipping & Tracking Information</h5>
    </div>
    <div class="card-body p-4 bg-light">
        <form method="POST" action="../tracking_module_src/TrackingAPI.php" class="row gx-3 gy-4 align-items-end" id="adminTrackUpdateForm">
            <input type="hidden" name="action" value="admin_update_tracking">
            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
            
            <div class="col-md-3">
                <label class="form-label fw-bold small text-muted">Order Status</label>
                <select name="status" class="form-select bg-white">
                    <option value="pending" <?php echo ($currentTracking['current_status']??'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo ($currentTracking['current_status']??'') === 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="partially_shipped" <?php echo ($currentTracking['current_status']??'') === 'partially_shipped' ? 'selected' : ''; ?>>Partially Shipped</option>
                    <option value="shipped" <?php echo ($currentTracking['current_status']??'') === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo ($currentTracking['current_status']??'') === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo ($currentTracking['current_status']??'') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label fw-bold small text-muted">Courier Partner</label>
                <select name="courier_id" class="form-select bg-white">
                    <option value="0">Self-Shipped / External</option>
                    <?php foreach($active_couriers as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($currentTracking['courier_id']??0) == $c['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label fw-bold small text-muted">AWB / Tracking Number</label>
                <input type="text" name="tracking_number" class="form-control bg-white" placeholder="e.g. DEL12345678" value="<?php echo htmlspecialchars($currentTracking['tracking_number']??''); ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label fw-bold small text-muted">Est. Delivery Date</label>
                <input type="date" name="estimated_delivery_date" class="form-control bg-white" value="<?php echo htmlspecialchars($currentTracking['estimated_delivery_date']??''); ?>">
            </div>
            
            <div class="col-12 text-end border-top pt-3 mt-4">
                <button type="submit" class="btn btn-primary px-4 shadow-sm"><i class="fas fa-save me-2"></i>Update Logistics Log</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Intercept default API POST and reload page using JS to feel natural
    document.getElementById('adminTrackUpdateForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('../tracking_module_src/TrackingAPI.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success || data.status === 'success') {
                alert('Order Tracking Updated Successfully!');
                location.reload(); 
            } else {
                alert('Failed to update tracking: ' + (data.message || data.error));
            }
        });
    });
</script>
