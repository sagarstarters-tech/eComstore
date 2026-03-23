<?php
/**
 * DashboardService
 * All business logic and data fetching for the admin dashboard.
 */
class DashboardService
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Get top-level stat counts.
     */
    public function getStats(): array
    {
        return [
            'users'       => (int)$this->conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'],
            'products'    => (int)$this->conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'],
            'orders'      => (int)$this->conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'],
            'sales_total' => (float)($this->conn->query("SELECT SUM(total_amount) as s FROM orders WHERE status != 'cancelled'")->fetch_assoc()['s'] ?? 0),
        ];
    }

    /**
     * Get last 7 days of daily sales totals for the chart.
     * Returns ['labels' => [...], 'data' => [...]]
     */
    public function getWeeklySales(): array
    {
        $labels = [];
        $data   = [];

        for ($i = 6; $i >= 0; $i--) {
            $date     = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('M d', strtotime($date));

            $stmt = $this->conn->prepare(
                "SELECT SUM(total_amount) as s FROM orders WHERE DATE(created_at) = ? AND status != 'cancelled'"
            );
            $stmt->bind_param('s', $date);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $data[] = (float)($result['s'] ?? 0);
            $stmt->close();
        }

        return compact('labels', 'data');
    }

    /**
     * Get the 5 most recent orders with customer name.
     */
    public function getRecentOrders(): array
    {
        $rows   = [];
        $result = $this->conn->query(
            "SELECT o.id, o.total_amount, o.status, u.name as user_name
             FROM orders o
             JOIN users u ON o.user_id = u.id
             ORDER BY o.created_at DESC
             LIMIT 5"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}
