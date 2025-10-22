<?php
require_once "config.php";
include 'admin_header.php';

// Default to all time if no filter
$filter = $_GET['filter'] ?? 'all';
$where_clause = '';
$page_title = 'All Time';

switch ($filter) {
    case 'today':
        $where_clause = "WHERE DATE(o.order_date) = CURDATE()";
        $page_title = 'Today';
        break;
    case '7days':
        $where_clause = "WHERE o.order_date >= NOW() - INTERVAL 7 DAY";
        $page_title = 'Last 7 Days';
        break;
    case 'lastweek':
        $where_clause = "WHERE YEARWEEK(o.order_date) = YEARWEEK(NOW() - INTERVAL 1 WEEK)";
        $page_title = 'Last Week';
        break;
    case 'lastmonth':
        $where_clause = "WHERE MONTH(o.order_date) = MONTH(NOW() - INTERVAL 1 MONTH) AND YEAR(o.order_date) = YEAR(NOW() - INTERVAL 1 MONTH)";
        $page_title = 'Last Month';
        break;
}


$history_query = "SELECT o.id, u.full_name, o.order_date, o.total_amount, o.status FROM orders o JOIN users u ON o.user_id = u.id $where_clause ORDER BY o.order_date DESC";
$history_result = $conn->query($history_query);
?>
<div class="container-fluid">
    <h1 class="page-header">Sales & Order History</h1>

    <div class="card mb-4">
        <div class="card-header">Sales Analytics (Last 30 Days)</div>
        <div class="card-body">
            <div id="salesChartDiv" style="height: 400px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                <!-- Chart will be rendered here, or a message if no data -->
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Order History: <?php echo $page_title; ?>
            <div class="btn-group float-end">
                 <a href="generate_pdf.php?filter=<?php echo $filter; ?>" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf-fill"></i> Download PDF</a>
                <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    Filter By
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="?filter=all">All Time</a></li>
                    <li><a class="dropdown-item" href="?filter=today">Today</a></li>
                    <li><a class="dropdown-item" href="?filter=7days">Last 7 Days</a></li>
                    <li><a class="dropdown-item" href="?filter=lastweek">Last Week</a></li>
                    <li><a class="dropdown-item" href="?filter=lastmonth">Last Month</a></li>
                </ul>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($history_result->num_rows > 0): ?>
                            <?php while($row = $history_result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($row['order_date'])); ?></td>
                                <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted p-4">No order history found for this period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        fetch('get_sales_data.php')
            .then(response => response.json())
            .then(data => {
                
                if (data && data.labels && data.labels.length > 0) {
                    
                    const points = data.labels.map((label, index) => {
                        return { x: label, y: parseFloat(data.values[index]) || 0 };
                    });

                    JSC.Chart('salesChartDiv', {
                        type: 'column',
                        title: {
                            label: { text: 'Daily Sales Performance', style: { fontSize: 18, fontWeight: 'bold' } },
                            position: 'center'
                        },
                        
                        // =================================
                        // FINAL FIX: Using a formatter function for robust currency display.
                        // This is the correct and most stable method.
                        // =================================
                        yAxis: {
                            label_text: 'Total Sales (PHP)',
                            // Use a formatter function to manually build the label string
                            label_formatter: function(value) {
                                // 'toLocaleString' is a standard JS function to format numbers with commas.
                                return '₱' + value.toLocaleString('en-US'); 
                            }
                        },
                        // ===============================
                        
                        xAxis: { label_text: 'Date' },
                        legend_visible: false,
                        defaultSeries: {
                            defaultPoint: {
                                // The tooltip uses a simple token that works reliably.
                                tooltip: '<b>%xValue</b><br>Sales: <b>₱%yValue</b>'
                            }
                        },
                        series: [ { name: 'Sales', points: points, color: '#008080' } ]
                    });

                } else {
                    document.getElementById('salesChartDiv').innerHTML = '<p class="text-center text-muted">No sales data available for the last 30 days. The chart will appear here once you have sales.</p>';
                }
                
            })
            .catch(error => {
                console.error('Error fetching or rendering chart:', error);
                document.getElementById('salesChartDiv').innerHTML = '<p class="text-center text-danger">Could not load sales data. Please check the `get_sales_data.php` file and the browser console for errors.</p>';
            });
    });
</script>

<?php include 'admin_footer.php'; ?>