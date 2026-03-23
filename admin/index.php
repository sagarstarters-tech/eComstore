<?php
include 'admin_header.php';

// Fetch Statistics
$users_count = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];
$products_count = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$orders_count = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$sales_total = $conn->query("SELECT SUM(total_amount) as s FROM orders WHERE status != 'cancelled'")->fetch_assoc()['s'] ?? 0;

// Sales Data for Chart (Last 7 Days)
$labels = [];
$data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('M d', strtotime($date));
    $sum = $conn->query("SELECT SUM(total_amount) as s FROM orders WHERE DATE(created_at) = '$date' AND status != 'cancelled'")->fetch_assoc()['s'] ?? 0;
    $data[] = $sum;
}
?>

<div class="row g-4 mb-5">
    <!-- Stat Cards -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-primary text-white">
            <div class="card-body d-flex align-items-center justify-content-between p-4">
                <div>
                    <h6 class="text-uppercase mb-2" style="letter-spacing: 1px;">Total Users</h6>
                    <h2 class="fw-bold mb-0"><?php echo number_format($users_count); ?></h2>
                </div>
                <i class="fas fa-users fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-success text-white">
            <div class="card-body d-flex align-items-center justify-content-between p-4">
                <div>
                    <h6 class="text-uppercase mb-2" style="letter-spacing: 1px;">Total Sales</h6>
                    <h2 class="fw-bold mb-0"><?php echo $global_currency; ?><?php echo number_format($sales_total, 2); ?></h2>
                </div>
                <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-warning text-dark">
            <div class="card-body d-flex align-items-center justify-content-between p-4">
                <div>
                    <h6 class="text-uppercase mb-2" style="letter-spacing: 1px;">Total Orders</h6>
                    <h2 class="fw-bold mb-0"><?php echo number_format($orders_count); ?></h2>
                </div>
                <i class="fas fa-shopping-cart fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-info text-white">
            <div class="card-body d-flex align-items-center justify-content-between p-4">
                <div>
                    <h6 class="text-uppercase mb-2" style="letter-spacing: 1px;">Products</h6>
                    <h2 class="fw-bold mb-0"><?php echo number_format($products_count); ?></h2>
                </div>
                <i class="fas fa-box fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Chart -->
    <div class="col-lg-8 mb-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">Sales Overview (Last 7 Days)</h5>
                <canvas id="salesChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <!-- Recent Orders -->
    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">Recent Orders</h5>
                <?php
                $recent = $conn->query("SELECT o.id, o.total_amount, o.status, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
                if ($recent && $recent->num_rows > 0):
                ?>
                <ul class="list-group list-group-flush">
                    <?php while($r = $recent->fetch_assoc()): ?>
                    <li class="list-group-item px-0 border-bottom border-light d-flex justify-content-between align-items-center">
                        <div>
                            <p class="fw-bold mb-0">#<?php echo $r['id']; ?> - <?php echo htmlspecialchars($r['user_name']); ?></p>
                            <span class="badge bg-<?php echo $r['status'] == 'pending' ? 'warning text-dark' : ($r['status'] == 'shipped' ? 'info' : ($r['status'] == 'delivered' ? 'success' : 'secondary')); ?> px-2 py-1 mt-1">
                                <?php echo ucfirst($r['status']); ?>
                            </span>
                        </div>
                        <span class="fw-bold text-success"><?php echo $global_currency; ?><?php echo number_format($r['total_amount'], 2); ?></span>
                    </li>
                    <?php endwhile; ?>
                </ul>
                <?php else: ?>
                <p class="text-muted text-center pt-3">No recent orders.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Sales ($)',
                data: <?php echo json_encode($data); ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>

<?php include 'admin_footer.php'; ?>
