<?php
include 'admin_header.php';

// Pagination setup
$items_per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Fetch totals
$total_q = $conn->query("SELECT COUNT(*) as cnt FROM email_logs");
$total_items = $total_q->fetch_assoc()['cnt'];
$total_pages = ceil($total_items / $items_per_page);

// Fetch logs
$query = "SELECT el.*, o.id as order_number, u.name as customer_name 
          FROM email_logs el 
          LEFT JOIN orders o ON el.order_id = o.id 
          LEFT JOIN users u ON o.user_id = u.id 
          ORDER BY el.created_at DESC 
          LIMIT $offset, $items_per_page";
$logs = $conn->query($query);

// Handle Delete Action
$success_msg = '';
$error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete' && isset($_POST['log_id'])) {
        $log_id = intval($_POST['log_id']);
        if ($conn->query("DELETE FROM email_logs WHERE id = $log_id")) {
            $success_msg = "Activity log entry deleted successfully.";
            // Refresh logs
            $logs = $conn->query($query);
        } else {
            $error_msg = "Error deleting log entry.";
        }
    } elseif ($action === 'clear_all') {
        if ($conn->query("TRUNCATE TABLE email_logs")) {
            $success_msg = "All email activity logs have been permanently deleted.";
            $logs = $conn->query($query); // Re-fetch (will be empty)
        } else {
            $error_msg = "Failed to clear email logs: " . $conn->error;
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Email Activity Logs</h4>
    <?php if ($total_items > 0): ?>
        <form method="POST" class="m-0" onsubmit="return confirm('WARNING: This will permanently delete ALL email activity logs from the database. This action cannot be undone. Are you absolutely sure?');">
    <?php echo csrf_input(); ?>
            <input type="hidden" name="action" value="clear_all">
            <button type="submit" class="btn btn-outline-danger btn-sm px-3"><i class="fas fa-trash-alt me-2"></i>Clear All Data</button>
        </form>
    <?php endif; ?>
</div>

<?php if($success_msg): ?>
    <div class="alert alert-success mt-3"><?php echo $success_msg; ?></div>
<?php endif; ?>
<?php if($error_msg): ?>
    <div class="alert alert-danger mt-3"><?php echo $error_msg; ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="py-3">Date/Time</th>
                        <th class="py-3">Order ID</th>
                        <th class="py-3">Recipient</th>
                        <th class="py-3">Type</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3">Error Details</th>
                        <th class="py-3 text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($logs && $logs->num_rows > 0): ?>
                        <?php while($log = $logs->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="d-block mb-1"><?php echo date('M d, Y', strtotime($log['created_at'])); ?></span>
                                <small class="text-muted"><?php echo date('h:i A', strtotime($log['created_at'])); ?></small>
                            </td>
                            <td>
                                <?php if($log['order_id']): ?>
                                    <span class="fw-bold text-primary">#<?php echo $log['order_number']; ?></span>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($log['customer_name']); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($log['recipient_email']); ?></td>
                            <td>
                                <?php if($log['email_type'] === 'customer_order'): ?>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info px-2 py-1">Customer Auth</span>
                                <?php else: ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-1">Admin Alert</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if($log['status'] === 'success'): ?>
                                    <span class="badge bg-success rounded-pill px-3 py-2"><i class="fas fa-check me-1"></i>Sent</span>
                                <?php else: ?>
                                    <span class="badge bg-danger rounded-pill px-3 py-2"><i class="fas fa-times me-1"></i>Failed</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($log['error_message']): ?>
                                    <button class="btn btn-sm btn-outline-danger" data-mdb-toggle="modal" data-mdb-target="#errorModal<?php echo $log['id']; ?>">
                                        View Error
                                    </button>
                                    
                                    <!-- Error Modal -->
                                    <div class="modal fade" id="errorModal<?php echo $log['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content rounded-4 border-0">
                                                <div class="modal-header border-bottom border-danger bg-danger bg-opacity-10">
                                                    <h5 class="modal-title fw-bold text-danger"><i class="fas fa-exclamation-circle me-2"></i>Delivery Error</h5>
                                                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <p class="mb-0 text-dark"><?php echo nl2br(htmlspecialchars($log['error_message'])); ?></p>
                                                </div>
                                                <div class="modal-footer border-0">
                                                    <button type="button" class="btn btn-light" data-mdb-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4">
                                <div class="d-flex justify-content-end align-items-center gap-2">
                                    <button class="btn btn-info btn-sm btn-custom px-3" data-mdb-toggle="modal" data-mdb-target="#viewLogModal<?php echo $log['id']; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <form method="POST" class="m-0 p-0" onsubmit="return confirm('Are you sure you want to delete this log entry?');">
    <?php echo csrf_input(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm btn-custom px-3"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </div>

                                <!-- View Modal -->
                                <div class="modal fade" id="viewLogModal<?php echo $log['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog text-start">
                                        <div class="modal-content rounded-4 border-0">
                                            <div class="modal-header bg-light">
                                                <h5 class="modal-title fw-bold">Log Details #<?php echo $log['id']; ?></h5>
                                                <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-muted small text-uppercase">Timestamp</label>
                                                    <p class="mb-0 fs-5"><?php echo date('M d, Y - h:i A', strtotime($log['created_at'])); ?></p>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-muted small text-uppercase">Recipient Email</label>
                                                    <p class="mb-0 fs-5"><?php echo htmlspecialchars($log['recipient_email']); ?></p>
                                                </div>
                                                
                                                <div class="mb-3 d-flex justify-content-between">
                                                    <div>
                                                        <label class="form-label fw-bold text-muted small text-uppercase mb-1">Email Type</label>
                                                        <div>
                                                            <?php if($log['email_type'] === 'customer_order'): ?>
                                                                <span class="badge bg-info bg-opacity-10 text-info border border-info">Customer Auth</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">Admin Alert</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="form-label fw-bold text-muted small text-uppercase mb-1">Status</label>
                                                        <div>
                                                            <?php if($log['status'] === 'success'): ?>
                                                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>Sent</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Failed</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <?php if($log['error_message']): ?>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-danger small text-uppercase">Error Message</label>
                                                    <div class="p-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded text-danger">
                                                        <?php echo nl2br(htmlspecialchars($log['error_message'])); ?>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <?php if($log['order_id']): ?>
                                                <div class="mt-4 pt-3 border-top">
                                                    <h6 class="fw-bold mb-2">Associated Order Data</h6>
                                                    <p class="mb-1"><strong>Order ID:</strong> #<?php echo $log['order_number']; ?></p>
                                                    <p class="mb-0"><strong>Customer Name:</strong> <?php echo htmlspecialchars($log['customer_name']); ?></p>
                                                    <a href="manage_orders.php" class="btn btn-outline-primary btn-sm mt-2">View Orders Dashboard</a>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer border-0 bg-light">
                                                <button type="button" class="btn btn-secondary btn-custom" data-mdb-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-envelope-open fa-3x mb-3 text-light"></i><br>No email activity logged yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
