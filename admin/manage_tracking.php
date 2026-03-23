<?php
include 'admin_header.php';

// Include Tracking Module logic
require_once '../tracking_module_src/src/Config/TrackingConfig.php';
require_once '../tracking_module_src/src/Repositories/TrackingRepository.php';
require_once '../tracking_module_src/src/Services/TrackingService.php';

use TrackingModule\Config\TrackingConfig;
use TrackingModule\Repositories\TrackingRepository;
use TrackingModule\Services\TrackingService;

$trackingConfig = new TrackingConfig();
$pdo = $trackingConfig->getConnection();
$repo = new TrackingRepository($pdo);
$service = new TrackingService($repo);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_tracking') {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    $courier_id = intval($_POST['courier_id']);
    $tracking_num = $_POST['tracking_number'];
    $est_date = $_POST['estimated_delivery_date'];

    try {
        $res = $service->adminUpdateTracking($order_id, $courier_id, $tracking_num, $est_date, $status);
        if ($res['success']) $success = $res['message'];
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all orders with their current tracking info
$orders_q = $conn->query("
    SELECT o.*, u.email as user_email, t.tracking_number, t.courier_id, c.name as courier_name, t.estimated_delivery_date
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    LEFT JOIN order_tracking t ON o.id = t.order_id
    LEFT JOIN courier_companies c ON t.courier_id = c.id
    ORDER BY o.created_at DESC
");

$couriers = $repo->getActiveCouriers();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12 px-4 pt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0 text-primary"><i class="fas fa-shipping-fast me-2"></i>Order Tracking Management</h4>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success border-0 shadow-sm rounded-4 py-2 mb-4"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-4 py-2 mb-4"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer Email</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Courier</th>
                                    <th>Tracking #</th>
                                    <th>Est. Delivery</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($orders_q && $orders_q->num_rows > 0): ?>
                                    <?php while($o = $orders_q->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?php echo $o['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($o['user_email']); ?></td>
                                        <td>$<?php echo number_format($o['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge rounded-pill <?php 
                                                echo match($o['status']) {
                                                    'pending' => 'bg-secondary',
                                                    'processing' => 'bg-info',
                                                    'shipped', 'partially_shipped' => 'bg-primary',
                                                    'delivered', 'completed' => 'bg-success',
                                                    'cancelled' => 'bg-danger',
                                                    default => 'bg-info'
                                                };
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $o['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($o['courier_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($o['tracking_number'] ?? 'N/A'); ?></td>
                                        <td><?php echo $o['estimated_delivery_date'] ? date('M j, Y', strtotime($o['estimated_delivery_date'])) : 'N/A'; ?></td>
                                        <td>
                                            <button class="btn btn-outline-primary btn-sm rounded-pill px-3" 
                                                onclick='editTracking(<?php echo htmlspecialchars(json_encode([
                                                    "id" => $o["id"],
                                                    "status" => $o["status"],
                                                    "courier_id" => $o["courier_id"],
                                                    "tracking_number" => $o["tracking_number"],
                                                    "estimated_delivery_date" => $o["estimated_delivery_date"]
                                                ]), ENT_QUOTES, "UTF-8"); ?>)'>
                                                Update Info
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="text-center py-4">No orders found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Tracking Modal -->
<div class="modal fade" id="trackingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Update Tracking Info</h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
            </div>
            <form method="POST">
    <?php echo csrf_input(); ?>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="update_tracking">
                    <input type="hidden" name="order_id" id="modal_order_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Shipping Status</label>
                        <select name="status" id="modal_status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="partially_shipped">Partially Shipped</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Courier</label>
                        <select name="courier_id" id="modal_courier_id" class="form-select">
                            <option value="0">Not Assigned</option>
                            <?php foreach($couriers as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Tracking Number</label>
                        <input type="text" name="tracking_number" id="modal_tracking_number" class="form-control" placeholder="AWB-XXXXXXXXX">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Estimated Delivery Date</label>
                        <input type="date" name="estimated_delivery_date" id="modal_est_date" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-mdb-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Update Tracking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let trackingModal;

document.addEventListener('DOMContentLoaded', () => {
    if (typeof mdb !== 'undefined') {
        trackingModal = new mdb.Modal(document.getElementById('trackingModal'));
    } else {
        console.error('MDB Library not loaded');
    }
});

function editTracking(order) {
    console.log('Editing tracking for order:', order);
    document.getElementById('modal_order_id').value = order.id;
    document.getElementById('modal_status').value = order.status;
    document.getElementById('modal_courier_id').value = order.courier_id || 0;
    document.getElementById('modal_tracking_number').value = order.tracking_number || '';
    document.getElementById('modal_est_date').value = order.estimated_delivery_date || '';
    
    if (trackingModal) {
        trackingModal.show();
    } else {
        // Fallback if modal hasn't initialized
        trackingModal = new mdb.Modal(document.getElementById('trackingModal'));
        trackingModal.show();
    }
}

function clearTrackingLogs() {
    if (!confirm('Are you sure you want to clear ALL tracking status history logs? This cannot be undone.')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'admin_clear_tracking_logs');

    fetch('../tracking_module_src/TrackingAPI.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.message || data.error));
        }
    })
    .catch(err => {
        alert('Fatal error occurred.');
        console.error(err);
    });
}
</script>

<?php include 'admin_footer.php'; ?>
