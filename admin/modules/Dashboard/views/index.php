<?php
/**
 * Dashboard View
 * Pure display view - no business logic.
 * Variables available: $stats, $sales_chart, $recent_orders, $global_currency
 */
?>
<div class="row g-4 mb-5">

    <!-- Stat Card: Users -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#2980b9,#3498db);">
            <div class="card-body d-flex align-items-center justify-content-between p-4 text-white">
                <div>
                    <h6 class="text-uppercase mb-2" style="letter-spacing:1px;opacity:.85;">Total Users</h6>
                    <h2 class="fw-bold mb-0"><?php echo number_format($stats['users']); ?></h2>
                </div>
                <i class="fas fa-users fa-3x opacity-25"></i>
            </div>
        </div>
    </div>

    <!-- Stat Card: Sales -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#27ae60,#2ecc71);">
            <div class="card-body d-flex align-items-center justify-content-between p-4 text-white">
                <div>
                    <h6 class="text-uppercase mb-2" style="letter-spacing:1px;opacity:.85;">Total Sales</h6>
                    <h2 class="fw-bold mb-0"><?php echo $global_currency . number_format($stats['sales_total'], 2); ?></h2>
                </div>
                <i class="fas fa-rupee-sign fa-3x opacity-25"></i>
            </div>
        </div>
    </div>

    <!-- Stat Card: Orders -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#e67e22,#f39c12);">
            <div class="card-body d-flex align-items-center justify-content-between p-4 text-white">
                <div>
                    <h6 class="text-uppercase mb-2" style="letter-spacing:1px;opacity:.85;">Total Orders</h6>
                    <h2 class="fw-bold mb-0"><?php echo number_format($stats['orders']); ?></h2>
                </div>
                <i class="fas fa-shopping-cart fa-3x opacity-25"></i>
            </div>
        </div>
    </div>

    <!-- Stat Card: Products -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#8e44ad,#9b59b6);">
            <div class="card-body d-flex align-items-center justify-content-between p-4 text-white">
                <div>
                    <h6 class="text-uppercase mb-2" style="letter-spacing:1px;opacity:.85;">Products</h6>
                    <h2 class="fw-bold mb-0"><?php echo number_format($stats['products']); ?></h2>
                </div>
                <i class="fas fa-box fa-3x opacity-25"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Sales Chart -->
    <div class="col-lg-8 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">Sales Overview <small class="text-muted fw-normal">(Last 7 Days)</small></h5>
                <canvas id="salesChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">Recent Orders</h5>
                <?php if (!empty($recent_orders)): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($recent_orders as $r): ?>
                    <li class="list-group-item px-0 border-bottom border-light d-flex justify-content-between align-items-center">
                        <div>
                            <p class="fw-bold mb-0">#<?php echo $r['id']; ?> &mdash; <?php echo htmlspecialchars($r['user_name']); ?></p>
                            <?php
                            $badge = match($r['status']) {
                                'pending'   => 'warning text-dark',
                                'shipped'   => 'info',
                                'delivered' => 'success',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default     => 'secondary',
                            };
                            ?>
                            <span class="badge bg-<?php echo $badge; ?> px-2 py-1 mt-1"><?php echo ucfirst($r['status']); ?></span>
                        </div>
                        <span class="fw-bold text-success"><?php echo $global_currency . number_format($r['total_amount'], 2); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="text-muted text-center pt-3">No recent orders.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($sales_chart['labels']); ?>,
            datasets: [{
                label: 'Sales (<?php echo $global_currency; ?>)',
                data: <?php echo json_encode($sales_chart['data']); ?>,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52,152,219,0.12)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#3498db',
                pointRadius: 5,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5,5] } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
