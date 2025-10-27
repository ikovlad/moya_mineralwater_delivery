<?php
require_once "config.php";
include 'admin_header.php';

// --- Fetch Sales Analytics (Only Completed Orders) ---
$today_sales = $conn->query("SELECT SUM(total_amount) as sales FROM orders WHERE DATE(order_date) = CURDATE() AND status = 'Completed'")->fetch_assoc()['sales'] ?? 0;
// $weekly_sales = $conn->query("SELECT SUM(total_amount) as sales FROM orders WHERE YEARWEEK(order_date, 1) = YEARWEEK(NOW(), 1) AND status = 'Completed'")->fetch_assoc()['sales'] ?? 0; // Removed weekly for brevity
$monthly_sales = $conn->query("SELECT SUM(total_amount) as sales FROM orders WHERE MONTH(order_date) = MONTH(NOW()) AND YEAR(order_date) = YEAR(NOW()) AND status = 'Completed'")->fetch_assoc()['sales'] ?? 0;
// $total_revenue = $conn->query("SELECT SUM(total_amount) as revenue FROM orders WHERE status = 'Completed'")->fetch_assoc()['revenue'] ?? 0; // Removed total revenue for brevity

// --- Fetch Other Stats ---
$pending_orders = $conn->query("SELECT COUNT(id) as count FROM orders WHERE status = 'Pending'")->fetch_assoc()['count'] ?? 0;
$total_customers = $conn->query("SELECT COUNT(id) as count FROM users")->fetch_assoc()['count'] ?? 0;

// --- Fetch Top Customers ---
$top_customers_query = "
    SELECT u.full_name, COUNT(o.id) as order_count
    FROM users u
    JOIN orders o ON u.id = o.user_id
    /* WHERE o.status = 'Completed' Optional: Count only completed orders */
    GROUP BY u.id
    ORDER BY order_count DESC
    LIMIT 5";
$top_customers_result = $conn->query($top_customers_query);

?>

<div class="container-fluid">
    <h1 class="page-header"><i class="bi bi-speedometer2"></i> Dashboard Overview</h1>

    <div class="row">
        <div class="col-lg-3 col-md-6 mb-4">
            <a href="admin_orders.php?status=Pending" class="text-decoration-none">
                <div class="card border-left-warning shadow h-100 py-2 card-hover">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Pending Orders</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_orders; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-clock-history fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <a href="admin_users.php" class="text-decoration-none">
                <div class="card border-left-primary shadow h-100 py-2 card-hover">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Customers</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_customers; ?></div>
                            </div>
                            <div class="col-auto">
                               <i class="bi bi-people-fill fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
             <a href="admin_sales.php?filter=today" class="text-decoration-none">
                <div class="card border-left-success shadow h-100 py-2 card-hover">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Today's Sales (Completed)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?php echo number_format($today_sales, 2); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-calendar-day fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
             <a href="admin_sales.php?filter=thismonth" class="text-decoration-none"> <div class="card border-left-info shadow h-100 py-2 card-hover">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">This Month's Sales (Completed)
                                </div>
                                <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">₱<?php echo number_format($monthly_sales, 2); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-calendar-month fs-2 text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

    </div><div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100"> <a href="admin_users.php" class="text-decoration-none text-body">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between card-header-hover">
                       <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-star-fill"></i> Top Customers</h6>
                       <i class="bi bi-arrow-right-circle text-primary"></i> </div>
                 </a>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Orders Placed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($top_customers_result->num_rows > 0):
                                    while ($row = $top_customers_result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><span class="badge bg-primary rounded-pill"><?php echo $row['order_count']; ?></span></td>
                                </tr>
                                <?php
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">No customer data yet.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100"> <a href="admin_orders.php?status=Pending" class="text-decoration-none text-body">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between card-header-hover">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-truck"></i> Incoming Orders (Real-Time)</h6>
                         <i class="bi bi-arrow-right-circle text-primary"></i> </div>
                 </a>
                <div class="card-body" id="real-time-orders" style="min-height: 250px; max-height: 400px; overflow-y: auto;">
                    <div class="text-center placeholder-glow pt-5"> <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading incoming orders...</p>
                    </div>
                </div>
            </div>
        </div>
    </div> </div> <style>
/* Styles for the border-left utilities (keep as is) */
.border-left-primary { border-left: .25rem solid #4e73df !important; }
.border-left-success { border-left: .25rem solid #1cc88a !important; }
.border-left-info { border-left: .25rem solid #36b9cc !important; }
.border-left-warning { border-left: .25rem solid #f6c23e !important; }
.text-xs { font-size: .8rem; } /* Slightly larger for readability */
.text-gray-300 { color: #dddfeb !important; }
.text-gray-800 { color: #5a5c69 !important; }
.font-weight-bold { font-weight: 700 !important; }
.shadow { box-shadow: 0 .15rem 1.75rem 0 rgba(58, 59, 69, .15) !important; }
.py-2 { padding-top: .5rem !important; padding-bottom: .5rem !important; }
.py-3 { padding-top: 1rem !important; padding-bottom: 1rem !important; }
.h-100 { height: 100% !important; }
.no-gutters { margin-right: 0; margin-left: 0; }
.no-gutters > .col, .no-gutters > [class*="col-"] { padding-right: 0; padding-left: 0; }

/* ADDED: Hover effect for clickable cards/headers */
.card-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 .3rem 1.75rem 0 rgba(58, 59, 69, .2) !important;
  transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}
.card-header-hover:hover {
  background-color: #f8f9fc; /* Light background on hover */
  cursor: pointer;
}
</style>

<script>
    function fetchRealTimeOrders() {
        fetch('get_realtime_orders.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text(); // Expecting HTML partial
            })
            .then(data => {
                const container = document.getElementById('real-time-orders');
                if (container) {
                     // Update content or show 'no orders' message
                     container.innerHTML = data.trim() || '<p class="text-center text-muted py-5">No incoming orders right now.</p>';
                } else {
                    console.error("Element with ID 'real-time-orders' not found.");
                }
            })
            .catch(error => {
                 console.error('Error fetching real-time orders:', error);
                 const container = document.getElementById('real-time-orders');
                 if (container) {
                     container.innerHTML = '<p class="text-danger text-center py-5">Could not load real-time orders. Check console for errors.</p>';
                 }
            });
    }

    // Fetch immediately on load and then set interval
    document.addEventListener('DOMContentLoaded', fetchRealTimeOrders);
    setInterval(fetchRealTimeOrders, 5000); // Refresh every 5 seconds
</script>

<?php include 'admin_footer.php'; ?>