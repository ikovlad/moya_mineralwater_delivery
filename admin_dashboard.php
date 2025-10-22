<?php
require_once "config.php";
include 'admin_header.php';

// Fetch Sales Analytics
$today_sales = $conn->query("SELECT SUM(total_amount) as sales FROM orders WHERE DATE(order_date) = CURDATE()")->fetch_assoc()['sales'] ?? 0;
$weekly_sales = $conn->query("SELECT SUM(total_amount) as sales FROM orders WHERE YEARWEEK(order_date) = YEARWEEK(NOW())")->fetch_assoc()['sales'] ?? 0;
$monthly_sales = $conn->query("SELECT SUM(total_amount) as sales FROM orders WHERE MONTH(order_date) = MONTH(NOW()) AND YEAR(order_date) = YEAR(NOW())")->fetch_assoc()['sales'] ?? 0;

// Fetch Top Customers
$top_customers_query = "
    SELECT u.full_name, COUNT(o.id) as order_count
    FROM users u
    JOIN orders o ON u.id = o.user_id
    GROUP BY u.id
    ORDER BY order_count DESC
    LIMIT 5";
$top_customers_result = $conn->query($top_customers_query);

?>

<div class="container-fluid">
    <h1 class="page-header">Dashboard</h1>

    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="card stat-card mb-3">
                <div class="card-header">Today's Sales</div>
                <div class="card-body">
                    <h5 class="card-title text-primary">₱<?php echo number_format($today_sales, 2); ?></h5>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card stat-card mb-3">
                <div class="card-header">This Week's Sales</div>
                <div class="card-body">
                    <h5 class="card-title text-success">₱<?php echo number_format($weekly_sales, 2); ?></h5>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card stat-card mb-3">
                <div class="card-header">This Month's Sales</div>
                <div class="card-body">
                    <h5 class="card-title text-info">₱<?php echo number_format($monthly_sales, 2); ?></h5>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="cardy">
                <div class="card-header">
                    Top Customers
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Orders</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($top_customers_result->num_rows > 0):
                                    while ($row = $top_customers_result->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo $row['order_count']; ?></td>
                                </tr>
                                <?php 
                                    endwhile;
                                else: 
                                ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted">No customer data yet.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="cardy">
                <div class="card-header">
                    Coming Orders (Real-Time)
                </div>
                <div class="card-body" id="real-time-orders">
                    <div class="text-center">
                        <div class="spinner-border" role="status" style="color: var(--moya-primary);">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function fetchRealTimeOrders() {
        fetch('get_realtime_orders.php')
            .then(response => response.text())
            .then(data => {
                document.getElementById('real-time-orders').innerHTML = data;
            });
    }

    setInterval(fetchRealTimeOrders, 5000); // Refresh every 5 seconds
    document.addEventListener('DOMContentLoaded', fetchRealTimeOrders);
</script>

<?php include 'admin_footer.php'; ?>