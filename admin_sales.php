<?php
require_once "config.php";
include 'admin_header.php';

// --- CONFIGURATION ---
$records_per_page = 10;

// --- GET CURRENT PAGE & FILTERS ---
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_filter = $_GET['filter'] ?? 'all'; // Keep existing date filter
$offset = ($page - 1) * $records_per_page;

// --- BUILD WHERE CLAUSE & PARAMS for DATE FILTER ---
$where_clauses = [];
$params = [];
$param_types = "";
$page_title = 'All Time';

switch ($date_filter) {
    case 'today':
        $where_clauses[] = "DATE(o.order_date) = CURDATE()";
        $page_title = 'Today';
        break;
    case '7days':
        $where_clauses[] = "o.order_date >= NOW() - INTERVAL 7 DAY";
        $page_title = 'Last 7 Days';
        break;
    case 'lastweek':
        $where_clauses[] = "YEARWEEK(o.order_date, 1) = YEARWEEK(NOW() - INTERVAL 1 WEEK, 1)"; // Added mode 1 for consistency
        $page_title = 'Last Week';
        break;
    case 'lastmonth':
        $where_clauses[] = "MONTH(o.order_date) = MONTH(NOW() - INTERVAL 1 MONTH) AND YEAR(o.order_date) = YEAR(NOW() - INTERVAL 1 MONTH)";
        $page_title = 'Last Month';
        break;
    // 'all' case needs no specific date clause added here
}

// --- APPEND SEARCH FILTER ---
if (!empty($search)) {
    // Check if search term is numeric to possibly search by ID
    $id_search_clause = is_numeric($search) ? "o.id = ?" : "1=0"; // 1=0 ensures it doesn't match if not numeric

    $where_clauses[] = "($id_search_clause OR u.full_name LIKE ?)"; // Search ID or Name

    if (is_numeric($search)) {
        $params[] = (int)$search; // Add ID param first if numeric
        $param_types .= "i";
    }

    $search_term_like = "%{$search}%";
    $params[] = $search_term_like; // Name param
    $param_types .= "s";
}


// --- APPEND STATUS FILTER ---
if (!empty($status_filter)) {
    $where_clauses[] = "o.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

// Combine all WHERE clauses
$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// --- COUNT TOTAL RECORDS (for pagination) ---
$count_sql = "SELECT COUNT(o.id)
              FROM orders o
              JOIN users u ON o.user_id = u.id
              $where_sql";

$stmt_count = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt_count->bind_param($param_types, ...$params);
}
$stmt_count->execute();
$stmt_count->bind_result($total_records);
$stmt_count->fetch();
$stmt_count->close();
$total_pages = ceil($total_records / $records_per_page);

// --- FETCH PAGINATED ORDERS ---
// Added o.status to SELECT list
$history_query = "
    SELECT o.id, u.full_name, o.order_date, o.total_amount, o.status
    FROM orders o
    JOIN users u ON o.user_id = u.id
    $where_sql
    ORDER BY o.order_date DESC
    LIMIT ? OFFSET ?"; // Add LIMIT and OFFSET

// Add LIMIT and OFFSET params for the main query
$params[] = $records_per_page;
$params[] = $offset;
$param_types .= "ii"; // Add types for LIMIT and OFFSET

$stmt_history = $conn->prepare($history_query);
if (!$stmt_history) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error); // Error handling
}
if (!empty($params)) {
    $stmt_history->bind_param($param_types, ...$params);
}
$stmt_history->execute();
$history_result = $stmt_history->get_result();

// Possible order statuses for the filter dropdown
$possible_statuses = ['Pending', 'Confirmed', 'Picked Up', 'On the Way', 'Completed', 'Cancelled'];

// --- Pagination Function (Arrow Style, includes all filters) ---
function generate_sales_pagination_links($current_page, $total_pages, $date_filter, $search_value, $status_value) {
    $links = '';
    $base_url = "admin_sales.php";
    $query_params = [];
    if (!empty($date_filter) && $date_filter != 'all') $query_params['filter'] = $date_filter;
    if (!empty($search_value)) $query_params['search'] = $search_value;
    if (!empty($status_value)) $query_params['status'] = $status_value;

    // --- Previous Arrow ---
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $temp_query_params = $query_params;
        $temp_query_params['page'] = $prev_page;
        $prev_url = $base_url . '?' . http_build_query($temp_query_params);
        $links .= '<li class="page-item"><a class="page-link" href="' . $prev_url . '" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link" aria-label="Previous"><span aria-hidden="true">&laquo;</span></span></li>';
    }

    // --- Current Page Number ---
    $links .= '<li class="page-item active" aria-current="page"><span class="page-link">' . $current_page . '</span></li>';

    // --- Next Arrow ---
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $temp_query_params = $query_params;
        $temp_query_params['page'] = $next_page;
        $next_url = $base_url . '?' . http_build_query($temp_query_params);
        $links .= '<li class="page-item"><a class="page-link" href="' . $next_url . '" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link" aria-label="Next"><span aria-hidden="true">&raquo;</span></span></li>';
    }

    return $links;
}
?>
<div class="container-fluid">
    <h1 class="page-header">Sales & Order History</h1>

    <div class="card mb-4">
        <div class="card-header">Sales Analytics (Last 30 Days)</div>
        <div class="card-body">
            <div id="salesChartDiv" style="height: 400px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
             <span>Order History: <?php echo $page_title; ?> (Page <?php echo $page; ?> of <?php echo $total_pages; ?>)</span>
             <div class="btn-group">
                 <a href="generate_pdf.php?filter=<?php echo $date_filter; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf-fill"></i> Download PDF</a>
                 <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                     Filter By Date
                 </button>
                 <ul class="dropdown-menu">
                     <li><a class="dropdown-item" href="?filter=all&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">All Time</a></li>
                     <li><a class="dropdown-item" href="?filter=today&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Today</a></li>
                     <li><a class="dropdown-item" href="?filter=7days&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Last 7 Days</a></li>
                     <li><a class="dropdown-item" href="?filter=lastweek&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Last Week</a></li>
                     <li><a class="dropdown-item" href="?filter=lastmonth&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Last Month</a></li>
                 </ul>
             </div>
        </div>
        <div class="card-body">
            <form method="GET" action="admin_sales.php" class="mb-4">
                <input type="hidden" name="filter" value="<?php echo $date_filter; // Keep date filter ?>">
                 <div class="row g-2">
                     <div class="col-md-5">
                         <input type="text" name="search" class="form-control" placeholder="Search Order ID or Name..." value="<?php echo htmlspecialchars($search); ?>">
                     </div>
                     <div class="col-md-4">
                         <select name="status" class="form-select">
                             <option value="">-- Filter by Status --</option>
                             <?php foreach ($possible_statuses as $stat): ?>
                                 <option value="<?php echo $stat; ?>" <?php echo ($status_filter == $stat) ? 'selected' : ''; ?>>
                                     <?php echo $stat; ?>
                                 </option>
                             <?php endforeach; ?>
                         </select>
                     </div>
                     <div class="col-md-3 d-flex">
                         <button class="btn btn-primary flex-grow-1 me-2" type="submit"><i class="bi bi-funnel-fill"></i> Apply Filters</button>
                         <?php if(!empty($search) || !empty($status_filter)): ?>
                             <a href="admin_sales.php?filter=<?php echo $date_filter; ?>" class="btn btn-secondary"><i class="bi bi-x-lg"></i> Clear Search/Status</a>
                         <?php endif; ?>
                     </div>
                 </div>
             </form>

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
                                <td>
                                     <?php // Added status badges for consistency
                                        $status = htmlspecialchars($row['status']);
                                        $badge_class = '';
                                        switch ($status) {
                                            case 'Pending': $badge_class = 'bg-warning text-dark'; break;
                                            case 'Confirmed': $badge_class = 'bg-info text-dark'; break;
                                            case 'Picked Up': $badge_class = 'bg-secondary'; break;
                                            case 'On the Way': $badge_class = 'bg-primary'; break;
                                            case 'Completed': $badge_class = 'bg-success'; break;
                                            case 'Cancelled': $badge_class = 'bg-danger'; break;
                                            default: $badge_class = 'bg-light text-dark';
                                        }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?> p-2"><?php echo $status; ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted p-4">
                                     <?php if(!empty($search) || !empty($status_filter) || $date_filter != 'all'): ?>
                                         No order history found matching your filters.
                                     <?php else: ?>
                                         No order history found.
                                     <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

             <?php if ($total_pages > 1): ?>
                <nav aria-label="Sales history navigation">
                    <ul class="pagination justify-content-center">
                        <?php echo generate_sales_pagination_links($page, $total_pages, $date_filter, $search, $status_filter); ?>
                    </ul>
                </nav>
            <?php endif; ?>

        </div> </div> </div> <script>
    // Keep the chart script exactly as it was
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
                        yAxis: {
                            label_text: 'Total Sales (PHP)',
                            label_formatter: function(value) {
                                return '₱' + value.toLocaleString('en-US');
                            }
                        },
                        xAxis: { label_text: 'Date' },
                        legend_visible: false,
                        defaultSeries: {
                            defaultPoint: {
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

<?php
// Close statement if it was prepared
if (isset($stmt_history) && $stmt_history instanceof mysqli_stmt) { $stmt_history->close(); }

include 'admin_footer.php';
?>