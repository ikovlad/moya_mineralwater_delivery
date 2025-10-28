<?php
require_once "config.php";
include 'admin_header.php';

// --- CONFIGURATION ---
$records_per_page = 10;

// --- GET CURRENT PAGE & FILTERS ---
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$offset = ($page - 1) * $records_per_page;

// --- BUILD WHERE CLAUSE & PARAMS ---
$where_clauses = [];
$params = [];
$param_types = "";

// Search filter (Order ID, Name, Phone)
if (!empty($search)) {
<<<<<<< HEAD
    // Check if search term is numeric to possibly search by ID
    $id_search_clause = is_numeric($search) ? "o.id = ?" : "1=0"; // 1=0 ensures it doesn't match if not numeric
    
    $where_clauses[] = "($id_search_clause OR u.full_name LIKE ? OR u.phone_number LIKE ?)";
    
    if (is_numeric($search)) {
        $params[] = (int)$search; // Add ID param first if numeric
=======
    $id_search_clause = is_numeric($search) ? "o.id = ?" : "1=0";
    $where_clauses[] = "($id_search_clause OR u.full_name LIKE ? OR u.phone_number LIKE ?)";
    
    if (is_numeric($search)) {
        $params[] = (int)$search;
>>>>>>> 81caf45 (try)
        $param_types .= "i";
    }
    
    $search_term_like = "%{$search}%";
<<<<<<< HEAD
    $params[] = $search_term_like; // Name param
    $params[] = $search_term_like; // Phone param
    $param_types .= "ss";
}


=======
    $params[] = $search_term_like;
    $params[] = $search_term_like;
    $param_types .= "ss";
}

>>>>>>> 81caf45 (try)
// Status filter
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
$orders_query = "
    SELECT o.id, u.full_name, u.phone_number, u.address_detail, u.address_barangay, o.order_date, o.status, o.total_amount
    FROM orders o
    JOIN users u ON o.user_id = u.id
    $where_sql
    ORDER BY
        CASE o.status
            WHEN 'Pending' THEN 1
            WHEN 'Confirmed' THEN 2
            WHEN 'Picked Up' THEN 3
            WHEN 'On the Way' THEN 4
<<<<<<< HEAD
            WHEN 'Completed' THEN 6  /* Moved Completed down */
            WHEN 'Cancelled' THEN 7  /* Moved Cancelled down */
            ELSE 5                   /* Other statuses */
        END, o.order_date DESC
    LIMIT ? OFFSET ?"; // Add LIMIT and OFFSET

// Add LIMIT and OFFSET params for the main query
$params[] = $records_per_page;
$params[] = $offset;
$param_types .= "ii"; // Add types for LIMIT and OFFSET

$stmt_orders = $conn->prepare($orders_query);
if (!$stmt_orders) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error); // Error handling
=======
            WHEN 'Completed' THEN 6
            WHEN 'Cancelled' THEN 7
            ELSE 5
        END, o.order_date DESC
    LIMIT ? OFFSET ?";

$params[] = $records_per_page;
$params[] = $offset;
$param_types .= "ii";

$stmt_orders = $conn->prepare($orders_query);
if (!$stmt_orders) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
>>>>>>> 81caf45 (try)
}
if (!empty($params)) {
    $stmt_orders->bind_param($param_types, ...$params);
}
$stmt_orders->execute();
$orders_result = $stmt_orders->get_result();

// Possible order statuses for the filter dropdown
$possible_statuses = ['Pending', 'Confirmed', 'Picked Up', 'On the Way', 'Completed', 'Cancelled'];

<<<<<<< HEAD
// Check for success/error messages from session (if needed, e.g., from update_order_status.php)
$alert_message = '';
if (isset($_SESSION['alert_message'])) {
    $alert_type = $_SESSION['alert_type'] ?? 'info';
    $alert_message = '<div class="alert alert-' . $alert_type . ' alert-dismissible fade show" role="alert">
        ' . htmlspecialchars($_SESSION['alert_message']) . '
=======
// Get status counts for stats
$status_counts = [];
foreach ($possible_statuses as $status) {
    $count_query = "SELECT COUNT(*) as count FROM orders WHERE status = ?";
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $status_counts[$status] = $result['count'];
    $stmt->close();
}

// Check for success/error messages from session
$alert_message = '';
if (isset($_SESSION['alert_message'])) {
    $alert_type = $_SESSION['alert_type'] ?? 'info';
    $icon_map = [
        'success' => 'check-circle-fill',
        'danger' => 'exclamation-triangle-fill',
        'warning' => 'exclamation-circle-fill',
        'info' => 'info-circle-fill'
    ];
    $icon = $icon_map[$alert_type] ?? 'info-circle-fill';
    
    $alert_message = '<div class="alert alert-' . $alert_type . ' alert-dismissible fade show alert-modern" role="alert">
        <i class="bi bi-' . $icon . ' me-2"></i>' . htmlspecialchars($_SESSION['alert_message']) . '
>>>>>>> 81caf45 (try)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    unset($_SESSION['alert_message']);
    unset($_SESSION['alert_type']);
}

<<<<<<< HEAD
// Function to generate pagination links (keeping filters)
=======
// Function to generate pagination links with numbers
>>>>>>> 81caf45 (try)
function generate_order_pagination_links($current_page, $total_pages, $search_value, $status_value) {
    $links = '';
    $base_url = "admin_orders.php";
    $query_params = [];
    if (!empty($search_value)) $query_params['search'] = $search_value;
    if (!empty($status_value)) $query_params['status'] = $status_value;
    
    // Previous Button
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $query_params['page'] = $prev_page;
<<<<<<< HEAD
        $links .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . http_build_query($query_params) . '">Previous</a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
=======
        $links .= '<li class="page-item"><a class="page-link page-link-arrow" href="' . $base_url . '?' . http_build_query($query_params) . '"><i class="bi bi-chevron-left"></i> Previous</a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link page-link-arrow"><i class="bi bi-chevron-left"></i> Previous</span></li>';
    }

    // Calculate page range to show
    $show_pages = 5; // Show max 5 page numbers
    $half = floor($show_pages / 2);
    
    $start = max(1, $current_page - $half);
    $end = min($total_pages, $current_page + $half);
    
    // Adjust if at the start
    if ($current_page <= $half) {
        $end = min($total_pages, $show_pages);
    }
    
    // Adjust if at the end
    if ($current_page > $total_pages - $half) {
        $start = max(1, $total_pages - $show_pages + 1);
    }

    // Show page numbers
    for ($i = $start; $i <= $end; $i++) {
        $query_params['page'] = $i;
        if ($i == $current_page) {
            $links .= '<li class="page-item active"><span class="page-link page-number">' . $i . '</span></li>';
        } else {
            $links .= '<li class="page-item"><a class="page-link page-number" href="' . $base_url . '?' . http_build_query($query_params) . '">' . $i . '</a></li>';
        }
    }

    // Show ellipsis and last page if needed
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $links .= '<li class="page-item disabled"><span class="page-link page-ellipsis">...</span></li>';
        }
        $query_params['page'] = $total_pages;
        $links .= '<li class="page-item"><a class="page-link page-number" href="' . $base_url . '?' . http_build_query($query_params) . '">' . $total_pages . '</a></li>';
>>>>>>> 81caf45 (try)
    }

    // Next Button
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $query_params['page'] = $next_page;
<<<<<<< HEAD
        $links .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . http_build_query($query_params) . '">Next</a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
=======
        $links .= '<li class="page-item"><a class="page-link page-link-arrow" href="' . $base_url . '?' . http_build_query($query_params) . '">Next <i class="bi bi-chevron-right"></i></a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link page-link-arrow">Next <i class="bi bi-chevron-right"></i></span></li>';
>>>>>>> 81caf45 (try)
    }

    return $links;
}
?>
<<<<<<< HEAD
<div class="container-fluid">
    <h1 class="page-header">Order Management</h1>
    
    <?php echo $alert_message; // Display session messages if any ?>

    <div class="card">
        <div class="card-header">
            All Orders (Page <?php echo $page; ?> of <?php echo $total_pages; ?>)
        </div>
        <div class="card-body">
            <form method="GET" action="admin_orders.php" class="mb-4">
                <div class="row g-2">
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" placeholder="Search by Order ID, Name, or Phone..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="status" class="form-select">
                            <option value="">-- Filter by Status --</option>
=======

<style>
/* Modern Order Management Styles */
.order-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.order-stat-card {
    background: white;
    border-radius: 0.75rem;
    padding: 1.25rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.08);
    border-left: 4px solid;
    transition: all 0.3s ease;
}

.order-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.15);
}

.order-stat-card.pending { border-left-color: #f6c23e; }
.order-stat-card.confirmed { border-left-color: #36b9cc; }
.order-stat-card.picked-up { border-left-color: #858796; }
.order-stat-card.on-the-way { border-left-color: #4e73df; }
.order-stat-card.completed { border-left-color: #1cc88a; }
.order-stat-card.cancelled { border-left-color: #e74a3b; }

.order-stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.order-stat-label {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #858796;
    letter-spacing: 0.5px;
}

.order-stat-icon {
    font-size: 1.25rem;
    opacity: 0.3;
}

.order-stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--moya-dark-text);
}

/* Filter Section */
.filter-section-modern {
    background: #f8f9fc;
    padding: 1.5rem;
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
}

.filter-section-modern .form-control,
.filter-section-modern .form-select {
    border: 1px solid #e3e6f0;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    border-radius: 0.5rem;
}

.filter-section-modern .form-control:focus,
.filter-section-modern .form-select:focus {
    border-color: var(--moya-primary);
    box-shadow: 0 0 0 3px rgba(0, 128, 128, 0.1);
}
.order-table-modern {
    margin: 0;
    font-size: 0.9rem;
}

.order-table-modern thead th {
    background: #f8f9fc;
    border: none;
    color: #858796;
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem 0.75rem;
    border-bottom: 2px solid #e3e6f0;
    white-space: nowrap;
}

.order-table-modern tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
}

.order-table-modern tbody tr {
    transition: all 0.2s ease;
}

.order-table-modern tbody tr:hover {
    background-color: #f8f9fc;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

/* Order ID Badge */
.order-id-badge {
    background: linear-gradient(135deg, rgba(0, 128, 128, 0.1) 0%, rgba(0, 128, 128, 0.05) 100%);
    color: var(--moya-primary);
    padding: 0.375rem 0.75rem;
    border-radius: 2rem;
    font-weight: 700;
    font-size: 0.875rem;
    display: inline-block;
}

/* Customer Info */
.customer-info {
    display: flex;
    flex-direction: column;
}

.customer-name {
    font-weight: 600;
    color: var(--moya-dark-text);
    margin-bottom: 0.25rem;
}

.customer-contact {
    font-size: 0.8rem;
    color: #858796;
}

.customer-contact i {
    font-size: 0.75rem;
}

/* Address Cell */
.address-cell {
    max-width: 200px;
}

.address-detail {
    color: var(--moya-dark-text);
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.address-barangay {
    display: inline-block;
    background: linear-gradient(135deg, rgba(54, 185, 204, 0.1) 0%, rgba(54, 185, 204, 0.05) 100%);
    color: #36b9cc;
    padding: 0.25rem 0.625rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Date Cell */
.date-cell {
    font-size: 0.875rem;
}

.date-primary {
    color: var(--moya-dark-text);
    font-weight: 600;
}

.time-secondary {
    color: #858796;
    font-size: 0.8rem;
    display: block;
}

/* Amount Cell */
.amount-cell {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--moya-primary);
}

/* Status Badges */
.status-badge-modern {
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}

.status-badge-modern i {
    font-size: 0.875rem;
}

.status-pending {
    background: linear-gradient(135deg, rgba(246, 194, 62, 0.2) 0%, rgba(246, 194, 62, 0.1) 100%);
    color: #c59217;
}

.status-confirmed {
    background: linear-gradient(135deg, rgba(54, 185, 204, 0.2) 0%, rgba(54, 185, 204, 0.1) 100%);
    color: #258391;
}

.status-picked-up {
    background: linear-gradient(135deg, rgba(133, 135, 150, 0.2) 0%, rgba(133, 135, 150, 0.1) 100%);
    color: #5a5c69;
}

.status-on-the-way {
    background: linear-gradient(135deg, rgba(78, 115, 223, 0.2) 0%, rgba(78, 115, 223, 0.1) 100%);
    color: #2e59d9;
}

.status-completed {
    background: linear-gradient(135deg, rgba(28, 200, 138, 0.2) 0%, rgba(28, 200, 138, 0.1) 100%);
    color: #17a673;
}

.status-cancelled {
    background: linear-gradient(135deg, rgba(231, 74, 59, 0.2) 0%, rgba(231, 74, 59, 0.1) 100%);
    color: #c0392b;
}

/* Action Dropdown */
.action-select-modern {
    border: 2px solid #e3e6f0;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
}

.action-select-modern:focus {
    border-color: var(--moya-primary);
    box-shadow: 0 0 0 3px rgba(0, 128, 128, 0.1);
}

.action-waiting {
    color: #858796;
    font-size: 0.8rem;
    font-style: italic;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.action-waiting i {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.action-final {
    color: #858796;
    font-size: 0.875rem;
    font-style: italic;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

/* Responsive */
@media (max-width: 1200px) {
    .order-stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .order-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .order-table-modern {
        font-size: 0.8rem;
    }
    
    .order-table-modern thead th,
    .order-table-modern tbody td {
        padding: 0.75rem 0.5rem;
    }
}

@media (max-width: 576px) {
    .order-stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid">
    <div class="page-header-modern">
        <h1><i class="bi bi-box-seam-fill"></i> Order Management</h1>
    </div>

    <?php echo $alert_message; ?>

    <!-- Status Stats Grid -->
    <div class="order-stats-grid">
        <div class="order-stat-card pending">
            <div class="order-stat-header">
                <span class="order-stat-label">Pending</span>
                <i class="bi bi-clock-history order-stat-icon" style="color: #f6c23e;"></i>
            </div>
            <div class="order-stat-value"><?php echo $status_counts['Pending'] ?? 0; ?></div>
        </div>
        
        <div class="order-stat-card confirmed">
            <div class="order-stat-header">
                <span class="order-stat-label">Confirmed</span>
                <i class="bi bi-check-circle order-stat-icon" style="color: #36b9cc;"></i>
            </div>
            <div class="order-stat-value"><?php echo $status_counts['Confirmed'] ?? 0; ?></div>
        </div>
        
        <div class="order-stat-card picked-up">
            <div class="order-stat-header">
                <span class="order-stat-label">Picked Up</span>
                <i class="bi bi-box order-stat-icon" style="color: #858796;"></i>
            </div>
            <div class="order-stat-value"><?php echo $status_counts['Picked Up'] ?? 0; ?></div>
        </div>
        
        <div class="order-stat-card on-the-way">
            <div class="order-stat-header">
                <span class="order-stat-label">On the Way</span>
                <i class="bi bi-truck order-stat-icon" style="color: #4e73df;"></i>
            </div>
            <div class="order-stat-value"><?php echo $status_counts['On the Way'] ?? 0; ?></div>
        </div>
        
        <div class="order-stat-card completed">
            <div class="order-stat-header">
                <span class="order-stat-label">Completed</span>
                <i class="bi bi-check-circle-fill order-stat-icon" style="color: #1cc88a;"></i>
            </div>
            <div class="order-stat-value"><?php echo $status_counts['Completed'] ?? 0; ?></div>
        </div>
        
        <div class="order-stat-card cancelled">
            <div class="order-stat-header">
                <span class="order-stat-label">Cancelled</span>
                <i class="bi bi-x-circle-fill order-stat-icon" style="color: #e74a3b;"></i>
            </div>
            <div class="order-stat-value"><?php echo $status_counts['Cancelled'] ?? 0; ?></div>
        </div>
    </div>

    <!-- Main Orders Card -->
    <div class="section-card-modern">
        <div class="section-card-header-modern">
            <h5><i class="bi bi-list-ul"></i> All Orders</h5>
            <span class="badge bg-secondary"><?php echo $total_records; ?> Total</span>
        </div>
        <div class="section-card-body-modern">
            <!-- Filters -->
            <form method="GET" action="admin_orders.php" class="mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search Order ID, Name, or Phone..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
>>>>>>> 81caf45 (try)
                            <?php foreach ($possible_statuses as $stat): ?>
                                <option value="<?php echo $stat; ?>" <?php echo ($status_filter == $stat) ? 'selected' : ''; ?>>
                                    <?php echo $stat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
<<<<<<< HEAD
                    <div class="col-md-3 d-flex">
                        <button class="btn btn-primary flex-grow-1 me-2" type="submit"><i class="bi bi-funnel-fill"></i> Apply Filters</button>
                        <?php if(!empty($search) || !empty($status_filter)): ?>
                            <a href="admin_orders.php" class="btn btn-secondary"><i class="bi bi-x-lg"></i> Clear</a>
=======
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-search me-1"></i>Search
                        </button>
                    </div>
                    <div class="col-md-3">
                        <?php if(!empty($search) || !empty($status_filter)): ?>
                            <a href="admin_orders.php" class="btn btn-secondary w-100">
                                <i class="bi bi-x-lg me-1"></i>Clear Filters
                            </a>
                        <?php else: ?>
                            <span class="text-muted small">Showing all <?php echo $total_records; ?> orders</span>
>>>>>>> 81caf45 (try)
                        <?php endif; ?>
                    </div>
                </div>
            </form>

<<<<<<< HEAD
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Admin Action</th>
=======
            <!-- Orders Table -->
            <div class="table-responsive">
                <table class="table order-table-modern">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Delivery Address</th>
                            <th>Order Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
>>>>>>> 81caf45 (try)
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders_result->num_rows > 0): ?>
<<<<<<< HEAD
                            <?php while($order = $orders_result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['address_detail'] . ', ' . $order['address_barangay']); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></td>
                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <?php
                                        $status = htmlspecialchars($order['status']);
                                        $badge_class = '';
                                        switch ($status) {
                                            case 'Pending': $badge_class = 'bg-warning text-dark'; break;
                                            case 'Confirmed': $badge_class = 'bg-info text-dark'; break;
                                            case 'Picked Up': $badge_class = 'bg-secondary'; break; // Changed style
                                            case 'On the Way': $badge_class = 'bg-primary'; break;
                                            case 'Completed': $badge_class = 'bg-success'; break;
                                            case 'Cancelled': $badge_class = 'bg-danger'; break;
                                            default: $badge_class = 'bg-light text-dark';
                                        }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?> p-2"><?php echo $status; ?></span>
=======
                            <?php while($order = $orders_result->fetch_assoc()): 
                                $status = htmlspecialchars($order['status']);
                                $status_class_map = [
                                    'Pending' => 'status-pending',
                                    'Confirmed' => 'status-confirmed',
                                    'Picked Up' => 'status-picked-up',
                                    'On the Way' => 'status-on-the-way',
                                    'Completed' => 'status-completed',
                                    'Cancelled' => 'status-cancelled'
                                ];
                                $status_icon_map = [
                                    'Pending' => 'clock-history',
                                    'Confirmed' => 'check-circle',
                                    'Picked Up' => 'box',
                                    'On the Way' => 'truck',
                                    'Completed' => 'check-circle-fill',
                                    'Cancelled' => 'x-circle-fill'
                                ];
                                $status_class = $status_class_map[$status] ?? '';
                                $status_icon = $status_icon_map[$status] ?? 'circle';
                            ?>
                            <tr>
                                <td>
                                    <span class="order-id-badge">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></span>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <span class="customer-name"><?php echo htmlspecialchars($order['full_name']); ?></span>
                                        <span class="customer-contact">
                                            <i class="bi bi-telephone-fill"></i> <?php echo htmlspecialchars($order['phone_number']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="address-cell">
                                    <div class="address-detail"><?php echo htmlspecialchars($order['address_detail']); ?></div>
                                    <span class="address-barangay"><?php echo htmlspecialchars($order['address_barangay']); ?></span>
                                </td>
                                <td class="date-cell">
                                    <span class="date-primary"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
                                    <span class="time-secondary"><?php echo date('h:i A', strtotime($order['order_date'])); ?></span>
                                </td>
                                <td class="amount-cell">
                                    ₱<?php echo number_format($order['total_amount'], 2); ?>
                                </td>
                                <td>
                                    <span class="status-badge-modern <?php echo $status_class; ?>">
                                        <i class="bi bi-<?php echo $status_icon; ?>"></i>
                                        <?php echo $status; ?>
                                    </span>
>>>>>>> 81caf45 (try)
                                </td>
                                <td>
                                    <?php
                                        $current_status = $order['status'];
                                        $is_final_state = in_array($current_status, ['Completed', 'Cancelled']);
                                    ?>

                                    <?php if ($is_final_state): ?>
<<<<<<< HEAD
                                        <span class="text-muted fst-italic">No action needed</span>
                                    <?php else: ?>
                                        <select class="form-select form-select-sm"
                                                onchange="updateOrderStatus(<?php echo $order['id']; ?>, this.value)">

                                            <option value="<?php echo $current_status; ?>" selected disabled hidden><?php echo $current_status; ?> (Change?)</option>

                                            <?php if ($current_status == 'Pending'): ?>
                                                <option value="Confirmed">Confirm Order</option>
                                            <?php elseif ($current_status == 'Confirmed'): ?>
                                                <option value="Confirmed" disabled>Waiting for User Pickup...</option>
                                            <?php elseif ($current_status == 'Picked Up'): ?>
                                                <option value="On the Way">Set Out for Delivery</option>
                                            <?php elseif ($current_status == 'On the Way'): ?>
                                                <option value="On the Way" disabled>Waiting for User Completion...</option>
                                            <?php endif; ?>
                                            
                                            <?php // Allow cancellation unless already completed/cancelled
                                                  if (!$is_final_state): ?>
                                                <option value="Cancelled">Cancel Order</option>
                                            <?php endif; ?>
                                        </select>
                                        <?php // Clarify waiting states
                                              if (in_array($current_status, ['Confirmed', 'On the Way'])): ?>
                                            <small class="text-muted d-block mt-1">Waiting for user</small>
=======
                                        <span class="action-final">
                                            <i class="bi bi-check-circle-fill"></i>
                                            Finalized
                                        </span>
                                    <?php else: ?>
                                        <select class="form-select form-select-sm action-select-modern"
                                                onchange="updateOrderStatus(<?php echo $order['id']; ?>, this.value)">

                                            <option value="<?php echo $current_status; ?>" selected disabled hidden>Update Status</option>

                                            <?php if ($current_status == 'Pending'): ?>
                                                <option value="Confirmed">✓ Confirm Order</option>
                                            <?php elseif ($current_status == 'Confirmed'): ?>
                                                <option value="Confirmed" disabled>⏳ Waiting for Pickup</option>
                                            <?php elseif ($current_status == 'Picked Up'): ?>
                                                <option value="On the Way">🚚 Out for Delivery</option>
                                            <?php elseif ($current_status == 'On the Way'): ?>
                                                <option value="On the Way" disabled>⏳ Waiting for Completion</option>
                                            <?php endif; ?>
                                            
                                            <?php if (!$is_final_state): ?>
                                                <option value="Cancelled">✕ Cancel Order</option>
                                            <?php endif; ?>
                                        </select>
                                        <?php if (in_array($current_status, ['Confirmed', 'On the Way'])): ?>
                                            <small class="action-waiting mt-1">
                                                <i class="bi bi-hourglass-split"></i>
                                                Waiting for customer
                                            </small>
>>>>>>> 81caf45 (try)
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
<<<<<<< HEAD
                                <td colspan="8" class="text-center text-muted p-4">
                                    <?php if(!empty($search) || !empty($status_filter)): ?>
                                        No orders found matching your filters.
                                    <?php else: ?>
                                        No orders found.
                                    <?php endif; ?>
=======
                                <td colspan="7">
                                    <div class="empty-state-modern">
                                        <i class="bi bi-inbox"></i>
                                        <h5>No Orders Found</h5>
                                        <p>
                                            <?php if(!empty($search) || !empty($status_filter)): ?>
                                                No orders match your search criteria
                                            <?php else: ?>
                                                No orders have been placed yet
                                            <?php endif; ?>
                                        </p>
                                    </div>
>>>>>>> 81caf45 (try)
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

<<<<<<< HEAD
             <?php if ($total_pages > 1): ?>
                <nav aria-label="Order navigation">
                    <ul class="pagination justify-content-center">
=======
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Order navigation">
                    <ul class="pagination pagination-modern justify-content-center">
>>>>>>> 81caf45 (try)
                        <?php echo generate_order_pagination_links($page, $total_pages, $search, $status_filter); ?>
                    </ul>
                </nav>
            <?php endif; ?>

<<<<<<< HEAD
        </div> </div> </div> <script>
function updateOrderStatus(orderId, newStatus) {
    if (newStatus === 'Cancelled') {
        if (!confirm('Are you sure you want to cancel Order #' + orderId + '? This cannot be undone.')) {
            // Find the select element and reset its value if cancellation is aborted
            let selectElement = event.target; // Get the select element that triggered the change
            selectElement.value = selectElement.options[0].value; // Reset to the hidden default option
            return; // Stop the function
        }
    }

=======
        </div>
    </div>

    <!-- Bottom Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="d-flex justify-content-center mt-3 mb-4">
        <nav aria-label="Order navigation bottom">
            <ul class="pagination pagination-modern">
                <?php echo generate_order_pagination_links($page, $total_pages, $search, $status_filter); ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
function updateOrderStatus(orderId, newStatus) {
    if (newStatus === 'Cancelled') {
        if (!confirm('⚠️ Are you sure you want to cancel Order #' + orderId + '?\n\nThis action cannot be undone.')) {
            let selectElement = event.target;
            selectElement.value = selectElement.options[0].value;
            return;
        }
    }

    // Show loading state
    let selectElement = event.target;
    selectElement.disabled = true;
    selectElement.style.opacity = '0.6';

>>>>>>> 81caf45 (try)
    fetch('update_order_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `order_id=${orderId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
<<<<<<< HEAD
            // Instead of full reload, maybe just update UI elements if needed,
            // but reload is simpler for now to reflect changes everywhere.
            location.reload(); 
        } else {
            alert('Failed to update status: ' + (data.message || 'Unknown error'));
             // Optionally reload even on failure to reset the dropdown
             // location.reload(); 
=======
            location.reload();
        } else {
            alert('❌ Failed to update status: ' + (data.message || 'Unknown error'));
            selectElement.disabled = false;
            selectElement.style.opacity = '1';
>>>>>>> 81caf45 (try)
        }
    })
    .catch(error => {
        console.error('Error:', error);
<<<<<<< HEAD
        alert('An error occurred while trying to update the status.');
        // location.reload(); // Optionally reload on network error
    });
}
</script>
<?php 
$stmt_orders->close(); // Close the prepared statement
=======
        alert('❌ An error occurred while updating the status.');
        selectElement.disabled = false;
        selectElement.style.opacity = '1';
    });
}
</script>

<?php 
$stmt_orders->close();
>>>>>>> 81caf45 (try)
include 'admin_footer.php'; 
?>