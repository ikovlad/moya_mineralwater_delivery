<?php
require_once "config.php";
include 'admin_header.php';

// --- CONFIGURATION ---
$records_per_page = 10;

// --- GET CURRENT PAGE & FILTERS ---
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_filter = $_GET['filter'] ?? 'all';
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
        $where_clauses[] = "YEARWEEK(o.order_date, 1) = YEARWEEK(NOW() - INTERVAL 1 WEEK, 1)";
        $page_title = 'Last Week';
        break;
    case 'lastmonth':
        $where_clauses[] = "MONTH(o.order_date) = MONTH(NOW() - INTERVAL 1 MONTH) AND YEAR(o.order_date) = YEAR(NOW() - INTERVAL 1 MONTH)";
        $page_title = 'Last Month';
        break;
}

// --- APPEND SEARCH FILTER ---
if (!empty($search)) {
    $id_search_clause = is_numeric($search) ? "o.id = ?" : "1=0";
    $where_clauses[] = "($id_search_clause OR u.full_name LIKE ?)";

    if (is_numeric($search)) {
        $params[] = (int)$search;
        $param_types .= "i";
    }

    $search_term_like = "%{$search}%";
    $params[] = $search_term_like;
    $param_types .= "s";
}

// --- APPEND STATUS FILTER ---
if (!empty($status_filter)) {
    $where_clauses[] = "o.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

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
$history_query = "
    SELECT o.id, u.full_name, o.order_date, o.total_amount, o.status
    FROM orders o
    JOIN users u ON o.user_id = u.id
    $where_sql
    ORDER BY o.order_date DESC
    LIMIT ? OFFSET ?";

$params[] = $records_per_page;
$params[] = $offset;
$param_types .= "ii";

$stmt_history = $conn->prepare($history_query);
if (!$stmt_history) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
if (!empty($params)) {
    $stmt_history->bind_param($param_types, ...$params);
}
$stmt_history->execute();
$history_result = $stmt_history->get_result();

// Possible order statuses for the filter dropdown
$possible_statuses = ['Pending', 'Confirmed', 'Picked Up', 'On the Way', 'Completed', 'Cancelled'];

// Enhanced pagination function matching admin_orders style
function generate_sales_pagination_links($current_page, $total_pages, $date_filter, $search_value, $status_value) {
    $links = '';
    $base_url = "admin_sales.php";
    $query_params = [];
    if (!empty($date_filter) && $date_filter != 'all') $query_params['filter'] = $date_filter;
    if (!empty($search_value)) $query_params['search'] = $search_value;
    if (!empty($status_value)) $query_params['status'] = $status_value;
    
    // Calculate page range
    $range = 2;
    $start_page = max(1, $current_page - $range);
    $end_page = min($total_pages, $current_page + $range);
    
    // Previous Button with arrow and text
    if ($current_page > 1) {
        $query_params['page'] = $current_page - 1;
        $links .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . http_build_query($query_params) . '"><i class="bi bi-chevron-left"></i> Previous</a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left"></i> Previous</span></li>';
    }
    
    // First page + ellipsis
    if ($start_page > 1) {
        $query_params['page'] = 1;
        $links .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . http_build_query($query_params) . '">1</a></li>';
        if ($start_page > 2) {
            $links .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Page numbers
    for ($i = $start_page; $i <= $end_page; $i++) {
        $query_params['page'] = $i;
        if ($i == $current_page) {
            $links .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $links .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . http_build_query($query_params) . '">' . $i . '</a></li>';
        }
    }
    
    // Last page + ellipsis
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $links .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $query_params['page'] = $total_pages;
        $links .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . http_build_query($query_params) . '">' . $total_pages . '</a></li>';
    }

    // Next Button with text and arrow
    if ($current_page < $total_pages) {
        $query_params['page'] = $current_page + 1;
        $links .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . http_build_query($query_params) . '">Next <i class="bi bi-chevron-right"></i></a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link">Next <i class="bi bi-chevron-right"></i></span></li>';
    }

    return $links;
}
?>

<style>
/* Enhanced Professional Styling - Matching admin_orders.php */
.page-header {
    color: #1e293b;
    font-weight: 700;
    font-size: 1.75rem;
    margin-bottom: 1.5rem;
    border-bottom: 3px solid #3b82f6;
    padding-bottom: 0.75rem;
}

.card {
    border: none;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border-radius: 0.75rem;
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.card-header {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    font-weight: 600;
    padding: 1.25rem 1.5rem;
    font-size: 1.1rem;
    border-bottom: none;
}

.card-body {
    padding: 1.5rem;
}

/* Filter Section Styling */
.filter-section {
    background: #f8fafc;
    border-radius: 0.5rem;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    border: 1px solid #e2e8f0;
}

.form-control:focus, .form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.15);
}

/* Table Enhancements */
.table-responsive {
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.table {
    margin-bottom: 0;
}

.table thead th {
    background-color: #f1f5f9;
    color: #475569;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #e2e8f0;
    padding: 1rem 0.75rem;
    white-space: nowrap;
}

.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background-color: #f8fafc;
    transform: scale(1.001);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.table td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    color: #334155;
}

/* Status Badges */
.badge {
    font-weight: 600;
    font-size: 0.75rem;
    padding: 0.5rem 0.85rem;
    border-radius: 0.375rem;
    letter-spacing: 0.025em;
    text-transform: uppercase;
}

/* Enhanced Pagination - Centered like image */
.pagination {
    margin: 0;
    gap: 0.5rem;
    justify-content: center;
}

.page-item {
    margin: 0;
}

.page-link {
    color: #64748b;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.625rem 1rem;
    font-weight: 500;
    transition: all 0.2s ease;
    min-width: 45px;
    text-align: center;
    background-color: white;
}

/* Specific styling for Previous/Next buttons */
.page-item:first-child .page-link,
.page-item:last-child .page-link {
    min-width: auto;
    padding: 0.625rem 1.25rem;
}

.page-link:hover {
    background-color: #f1f5f9;
    border-color: #94a3b8;
    color: #334155;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.page-item.active .page-link {
    background-color: #1e293b;
    border-color: #1e293b;
    color: white;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(30, 41, 59, 0.3);
}

.page-item.disabled .page-link {
    background-color: #f8fafc;
    border-color: #e2e8f0;
    color: #cbd5e1;
    cursor: not-allowed;
}

.page-link i {
    font-size: 0.75rem;
}

/* Button Enhancements */
.btn {
    font-weight: 600;
    border-radius: 0.5rem;
    padding: 0.625rem 1.25rem;
    transition: all 0.2s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border: none;
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
}

.btn-secondary {
    background-color: #64748b;
    border: none;
}

.btn-secondary:hover {
    background-color: #475569;
    transform: translateY(-1px);
}

.btn-danger {
    background-color: #dc2626;
    border: none;
}

.btn-danger:hover {
    background-color: #b91c1c;
    transform: translateY(-1px);
}

/* Dropdown Menu Styling */
.dropdown-menu {
    border: none;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    border-radius: 0.5rem;
}

.dropdown-item {
    padding: 0.625rem 1.25rem;
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background-color: #f1f5f9;
    color: #3b82f6;
}

/* Chart Container */
#salesChartDiv {
    background: #f8fafc;
    border-radius: 0.5rem;
    padding: 1rem;
}

/* Empty State */
.empty-state {
    padding: 3rem 1rem;
    text-align: center;
}

.empty-state i {
    font-size: 3rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .page-header {
        font-size: 1.5rem;
    }
    
    .card-header {
        font-size: 1rem;
        padding: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .filter-section {
        padding: 1rem;
    }
    
    .table {
        font-size: 0.875rem;
    }
    
    .pagination {
        font-size: 0.875rem;
    }
    
    .page-link {
        padding: 0.375rem 0.625rem;
        min-width: 35px;
    }
}
</style>

<div class="container-fluid">
    <h1 class="page-header">
        <i class="bi bi-graph-up me-2"></i>Sales & Order History
    </h1>

    <!-- Sales Chart Card -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-bar-chart-line me-2"></i>Sales Analytics (Last 30 Days)
        </div>
        <div class="card-body">
            <div id="salesChartDiv" style="height: 400px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
            </div>
        </div>
    </div>

    <!-- Order History Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>
                <i class="bi bi-clock-history me-2"></i>Order History: <?php echo $page_title; ?>
            </span>
            <div class="btn-group">
                <a href="generate_pdf.php?filter=<?php echo $date_filter; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                   class="btn btn-sm btn-danger">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download PDF
                </a>
                <button type="button" class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-calendar-range me-1"></i> Filter By Date
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item <?php echo $date_filter == 'all' ? 'active' : ''; ?>" href="?filter=all&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                        <i class="bi bi-infinity me-2"></i>All Time
                    </a></li>
                    <li><a class="dropdown-item <?php echo $date_filter == 'today' ? 'active' : ''; ?>" href="?filter=today&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                        <i class="bi bi-calendar-day me-2"></i>Today
                    </a></li>
                    <li><a class="dropdown-item <?php echo $date_filter == '7days' ? 'active' : ''; ?>" href="?filter=7days&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                        <i class="bi bi-calendar-week me-2"></i>Last 7 Days
                    </a></li>
                    <li><a class="dropdown-item <?php echo $date_filter == 'lastweek' ? 'active' : ''; ?>" href="?filter=lastweek&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                        <i class="bi bi-calendar3 me-2"></i>Last Week
                    </a></li>
                    <li><a class="dropdown-item <?php echo $date_filter == 'lastmonth' ? 'active' : ''; ?>" href="?filter=lastmonth&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                        <i class="bi bi-calendar-month me-2"></i>Last Month
                    </a></li>
                </ul>
            </div>
        </div>
        <div class="card-body">
            <!-- Filter Section with Auto-Submit -->
            <form method="GET" action="admin_sales.php" id="filterForm" class="filter-section">
                <input type="hidden" name="filter" value="<?php echo $date_filter; ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">
                            <i class="bi bi-search me-1"></i>Search
                        </label>
                        <input type="text" 
                               name="search" 
                               id="searchInput" 
                               class="form-control" 
                               placeholder="Order ID or Customer Name..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">
                            <i class="bi bi-funnel me-1"></i>Status Filter
                        </label>
                        <select name="status" 
                                id="statusFilter" 
                                class="form-select" 
                                onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Statuses</option>
                            <?php foreach ($possible_statuses as $stat): ?>
                                <option value="<?php echo $stat; ?>" <?php echo ($status_filter == $stat) ? 'selected' : ''; ?>>
                                    <?php echo $stat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <?php if(!empty($search) || !empty($status_filter)): ?>
                            <a href="admin_sales.php?filter=<?php echo $date_filter; ?>" 
                               class="btn btn-secondary w-100" 
                               title="Clear all filters">
                                <i class="bi bi-x-lg me-1"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
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
                                <td><strong>#<?php echo $row['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td>
                                    <small><?php echo date('M d, Y', strtotime($row['order_date'])); ?></small>
                                    <br>
                                    <small class="text-muted"><?php echo date('h:i A', strtotime($row['order_date'])); ?></small>
                                </td>
                                <td><strong class="text-success">₱<?php echo number_format($row['total_amount'], 2); ?></strong></td>
                                <td>
                                    <?php
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
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p class="text-muted mb-0">
                                            <?php if(!empty($search) || !empty($status_filter) || $date_filter != 'all'): ?>
                                                No order history found matching your filters.
                                            <?php else: ?>
                                                No order history available yet.
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination - Centered like the image -->
            <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-center mt-4 pt-3 border-top">
                    <nav aria-label="Sales history pagination">
                        <ul class="pagination mb-0">
                            <?php echo generate_sales_pagination_links($page, $total_pages, $date_filter, $search, $status_filter); ?>
                        </ul>
                    </nav>
                </div>
                <div class="text-center mt-2">
                    <small class="text-muted">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                        <span class="mx-2">•</span>
                        <?php echo $total_records; ?> total orders
                    </small>
                </div>
            <?php elseif ($total_records > 0): ?>
                <div class="text-center mt-3 pt-3 border-top">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Showing all <?php echo $total_records; ?> orders
                    </small>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// Auto-search with debounce for accurate results
let searchTimeout;
const searchInput = document.getElementById('searchInput');

if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        
        searchTimeout = setTimeout(function() {
            document.getElementById('filterForm').submit();
        }, 500);
    });
}

// Sales Chart Script
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
                    series: [ { name: 'Sales', points: points, color: '#3b82f6' } ]
                });
            } else {
                document.getElementById('salesChartDiv').innerHTML = '<div class="empty-state"><i class="bi bi-graph-up"></i><p class="text-muted">No sales data available for the last 30 days.<br>The chart will appear here once you have sales.</p></div>';
            }
        })
        .catch(error => {
            console.error('Error fetching or rendering chart:', error);
            document.getElementById('salesChartDiv').innerHTML = '<div class="empty-state"><i class="bi bi-exclamation-triangle text-danger"></i><p class="text-danger">Could not load sales data.<br>Please check the get_sales_data.php file.</p></div>';
        });
});
</script>

<?php
if (isset($stmt_history) && $stmt_history instanceof mysqli_stmt) { 
    $stmt_history->close(); 
}
include 'admin_footer.php';
?>