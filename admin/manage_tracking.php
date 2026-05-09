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
                                    <th>Tracking # (AWB)</th>
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
                                        <td>
                                            <?php if (!empty($o['tracking_number'])): ?>
                                                <span class="font-monospace fw-bold text-dark"><?php echo htmlspecialchars($o['tracking_number']); ?></span>
                                                <button class="btn btn-link btn-sm p-0 ms-1 text-muted" title="Copy AWB" onclick="copyAWB('<?php echo htmlspecialchars($o['tracking_number']); ?>')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">Not Assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $o['estimated_delivery_date'] ? date('M j, Y', strtotime($o['estimated_delivery_date'])) : 'N/A'; ?></td>
                                        <td class="text-nowrap">
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-outline-primary btn-sm px-3" style="border-radius: 50rem 0 0 50rem;"
                                                    onclick='editTracking(<?php echo htmlspecialchars(json_encode([
                                                        "id" => $o["id"],
                                                        "status" => $o["status"],
                                                        "courier_id" => $o["courier_id"],
                                                        "tracking_number" => $o["tracking_number"],
                                                        "estimated_delivery_date" => $o["estimated_delivery_date"]
                                                    ]), ENT_QUOTES, "UTF-8"); ?>)'>
                                                    <i class="fas fa-edit me-1"></i>Update
                                                </button>
                                                <?php if (!empty($o['tracking_number'])): ?>
                                                <button class="btn btn-outline-success btn-sm px-3" style="border-radius: 0 50rem 50rem 0;"
                                                    onclick="trackAWBShipment(<?php echo $o['id']; ?>, '<?php echo htmlspecialchars($o['tracking_number']); ?>', '<?php echo htmlspecialchars($o['courier_name'] ?? ''); ?>')">
                                                    <i class="fas fa-satellite-dish me-1"></i>Track AWB
                                                </button>
                                                <?php else: ?>
                                                <button class="btn btn-outline-secondary btn-sm px-3 disabled" style="border-radius: 0 50rem 50rem 0;">
                                                    <i class="fas fa-satellite-dish me-1"></i>No AWB
                                                </button>
                                                <?php endif; ?>
                                            </div>
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

<!-- AWB Shipment Tracking Modal -->
<div class="modal fade" id="awbTrackingModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-lg-down">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 bg-gradient" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                <div class="d-flex align-items-center">
                    <div class="bg-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fas fa-satellite-dish text-primary"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0">AWB Shipment Tracking</h5>
                        <small class="text-white-50" id="awb_modal_subtitle">Loading...</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="awb_modal_body">
                <!-- Dynamic content injected by JS -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                    <p class="mt-3 text-muted">Fetching shipment details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let trackingModal;
let awbModal;

document.addEventListener('DOMContentLoaded', () => {
    if (typeof mdb !== 'undefined') {
        trackingModal = new mdb.Modal(document.getElementById('trackingModal'));
        awbModal = new mdb.Modal(document.getElementById('awbTrackingModal'));
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
        trackingModal = new mdb.Modal(document.getElementById('trackingModal'));
        trackingModal.show();
    }
}

/**
 * Copy AWB number to clipboard
 */
function copyAWB(awb) {
    navigator.clipboard.writeText(awb).then(() => {
        // Show a brief toast notification
        showToast('AWB number copied: ' + awb, 'success');
    }).catch(() => {
        // Fallback for older browsers
        const input = document.createElement('input');
        input.value = awb;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        showToast('AWB number copied: ' + awb, 'success');
    });
}

/**
 * Track AWB Shipment — opens tracking details in modal
 */
function trackAWBShipment(orderId, awb, courierName) {
    // Update modal subtitle
    document.getElementById('awb_modal_subtitle').textContent = 
        `Order #${orderId} • ${courierName || 'Courier'} • AWB: ${awb}`;
    
    // Show loading state
    document.getElementById('awb_modal_body').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
            <p class="mt-3 text-muted">Fetching shipment details...</p>
        </div>
    `;
    
    // Show modal
    if (!awbModal) awbModal = new mdb.Modal(document.getElementById('awbTrackingModal'));
    awbModal.show();
    
    // Fetch AWB tracking data from our secure API
    fetch(`../api/awb_track.php?order_id=${orderId}&admin=1`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                renderAWBTrackingPanel(data.data);
            } else {
                document.getElementById('awb_modal_body').innerHTML = `
                    <div class="alert alert-warning m-4 rounded-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>${data.message || 'Unable to fetch tracking information.'}
                    </div>
                `;
            }
        })
        .catch(err => {
            console.error('AWB Tracking Error:', err);
            document.getElementById('awb_modal_body').innerHTML = `
                <div class="alert alert-danger m-4 rounded-3">
                    <i class="fas fa-times-circle me-2"></i>Network error occurred while fetching tracking data.
                </div>
            `;
        });
}

/**
 * Render the AWB tracking panel inside the modal
 */
function renderAWBTrackingPanel(data) {
    const hasUrl = data.tracking_url && data.tracking_url.trim() !== '';
    
    let html = `
        <div class="p-4">
            <!-- AWB Info Card -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card bg-light border-0 rounded-3 h-100">
                        <div class="card-body text-center p-3">
                            <div class="text-muted small mb-1"><i class="fas fa-hashtag me-1"></i>Order ID</div>
                            <div class="fw-bold fs-5 text-dark">#${data.order_id}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light border-0 rounded-3 h-100">
                        <div class="card-body text-center p-3">
                            <div class="text-muted small mb-1"><i class="fas fa-truck me-1"></i>Courier</div>
                            <div class="fw-bold fs-5 text-primary">${data.courier_name}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light border-0 rounded-3 h-100">
                        <div class="card-body text-center p-3">
                            <div class="text-muted small mb-1"><i class="fas fa-barcode me-1"></i>AWB Number</div>
                            <div class="fw-bold fs-5 text-success font-monospace">${data.awb}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="d-flex flex-wrap gap-2 justify-content-center mb-4">
                <button class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm" onclick="copyAWB('${data.awb}')">
                    <i class="fas fa-copy me-2"></i>Copy AWB Number
                </button>
                ${hasUrl ? `
                    <a href="${data.tracking_url}" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-lg rounded-pill px-4 shadow-sm">
                        <i class="fas fa-external-link-alt me-2"></i>Track on ${data.courier_name}
                    </a>
                ` : ''}
            </div>
            
            ${hasUrl ? `
            <!-- Embedded Tracking (iframe) -->
            <div class="card border rounded-3 overflow-hidden">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-2">
                    <span class="small text-muted"><i class="fas fa-globe me-1"></i>Live Tracking — ${data.courier_name}</span>
                    <a href="${data.tracking_url}" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        <i class="fas fa-expand me-1"></i>Open Full Page
                    </a>
                </div>
                <div class="position-relative" style="min-height: 500px; background: #f8f9fa;">
                    <iframe src="https://t.17track.net/en#nums=${data.awb}" 
                            id="awbTrackingIframe"
                            style="width: 100%; height: 500px; border: none;"
                            sandbox="allow-scripts allow-same-origin allow-popups allow-forms"
                            loading="lazy"
                            onload="document.getElementById('awbIframeLoader').style.display='none';"
                            onerror="handleIframeError()">
                    </iframe>
                    <div id="awbIframeLoader" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="background: rgba(248,249,250,0.95);">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <p class="text-muted mb-2">Loading ${data.courier_name} tracking page...</p>
                        <p class="text-muted small">If it doesn't load, courier may block embedding.</p>
                        <a href="${data.tracking_url}" target="_blank" class="btn btn-outline-primary btn-sm mt-2 rounded-pill">
                            <i class="fas fa-external-link-alt me-1"></i>Open Directly Instead
                        </a>
                    </div>
                </div>
            </div>
            ` : `
            <div class="alert alert-info rounded-3">
                <i class="fas fa-info-circle me-2"></i>
                No tracking URL configured for this courier. You can still use the AWB number above to manually track on the courier's website.
            </div>
            `}
        </div>
    `;
    
    document.getElementById('awb_modal_body').innerHTML = html;
    
    // Auto-hide iframe loader after 8 seconds (fallback for X-Frame-Options block)
    if (hasUrl) {
        setTimeout(() => {
            const loader = document.getElementById('awbIframeLoader');
            if (loader) loader.style.display = 'none';
        }, 8000);
    }
}

/**
 * Handle iframe load error (courier blocks embedding)
 */
function handleIframeError() {
    const loader = document.getElementById('awbIframeLoader');
    if (loader) {
        loader.innerHTML = `
            <div class="text-center">
                <i class="fas fa-shield-alt fa-3x text-warning mb-3"></i>
                <h6 class="fw-bold">Courier website blocked embedding</h6>
                <p class="text-muted small">This is normal — most courier sites restrict iframe loading for security.</p>
                <p class="text-muted small">Please use the "Track on Courier" button above to open tracking directly.</p>
            </div>
        `;
    }
}

/**
 * Show a brief toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'info'} position-fixed shadow-lg border-0 rounded-3`;
    toast.style.cssText = 'bottom: 20px; right: 20px; z-index: 99999; min-width: 250px; animation: fadeInUp 0.3s ease;';
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>${message}`;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 2500);
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

<style>
/* AWB Tracking Modal Styles */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

#awbTrackingModal .modal-content {
    overflow: hidden;
}

#awbTrackingModal .card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

#awbTrackingModal .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
}

.rounded-pill-start {
    border-radius: 50rem 0 0 50rem !important;
}

.rounded-pill-end {
    border-radius: 0 50rem 50rem 0 !important;
}
</style>

<?php include 'admin_footer.php'; ?>
