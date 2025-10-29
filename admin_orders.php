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

=======
>>>>>>> 93ac7ac (Added all)
// Check for success/error messages from session
$alert_message = '';
if (isset($_SESSION['alert_message'])) {
    $alert_type = $_SESSION['alert_type'] ?? 'info';
<<<<<<< HEAD
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
=======
    $alert_message = '<div class="alert alert-' . $alert_type . ' alert-dismissible fade show" role="alert">
        ' . htmlspecialchars($_SESSION['alert_message']) . '
>>>>>>> 93ac7ac (Added all)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    unset($_SESSION['alert_message']);
    unset($_SESSION['alert_type']);
}

<<<<<<< HEAD
<<<<<<< HEAD
// Function to generate pagination links (keeping filters)
=======
// Function to generate pagination links with numbers
>>>>>>> 81caf45 (try)
=======
// Enhanced pagination function
>>>>>>> 93ac7ac (Added all)
function generate_order_pagination_links($current_page, $total_pages, $search_value, $status_value) {
    $links = '';
    $base_url = "admin_orders.php";
    $query_params = [];
    if (!empty($search_value)) $query_params['search'] = $search_value;
    if (!empty($status_value)) $query_params['status'] = $status_value;
    
    // Calculate page range
    $range = 2; // Number of pages to show on each side of current page
    $start_page = max(1, $current_page - $range);
    $end_page = min($total_pages, $current_page + $range);
    
    // Previous Button with arrow and text
    if ($current_page > 1) {
<<<<<<< HEAD
        $prev_page = $current_page - 1;
        $query_params['page'] = $prev_page;
<<<<<<< HEAD
        $links .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . http_build_query($query_params) . '">Previous</a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
=======
        $links .= '<li class="page-item"><a class="page-link page-link-arrow" href="' . $base_url . '?' . http_build_query($query_params) . '"><i class="bi bi-chevron-left"></i> Previous</a></li>';
=======
        $query_params['page'] = $current_page - 1;
        $links .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . http_build_query($query_params) . '"><i class="bi bi-chevron-left"></i> Previous</a></li>';
>>>>>>> 93ac7ac (Added all)
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
<<<<<<< HEAD
        $links .= '<li class="page-item"><a class="page-link page-number" href="' . $base_url . '?' . http_build_query($query_params) . '">' . $total_pages . '</a></li>';
>>>>>>> 81caf45 (try)
=======
        $links .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . http_build_query($query_params) . '">' . $total_pages . '</a></li>';
>>>>>>> 93ac7ac (Added all)
    }

    // Next Button with text and arrow
    if ($current_page < $total_pages) {
<<<<<<< HEAD
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
=======
        $query_params['page'] = $current_page + 1;
        $links .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . http_build_query($query_params) . '">Next <i class="bi bi-chevron-right"></i></a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link">Next <i class="bi bi-chevron-right"></i></span></li>';
>>>>>>> 93ac7ac (Added all)
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
/* Enhanced Professional Styling */
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

/* Pagination Info */
.pagination-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
    color: #64748b;
    font-size: 0.875rem;
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

/* Form Select Styling */
.form-select-sm {
    border-radius: 0.375rem;
    border: 1px solid #e2e8f0;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.form-select-sm:hover {
    border-color: #cbd5e1;
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
        <i class="bi bi-clipboard-check me-2"></i>Order Management
    </h1>
    
    <?php echo $alert_message; ?>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-list-ul me-2"></i>All Orders
        </div>
        <div class="card-body">
            <!-- Filter Section -->
            <form method="GET" action="admin_orders.php" id="filterForm" class="filter-section">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">
                            <i class="bi bi-search me-1"></i>Search
                        </label>
                        <input type="text" 
                               name="search" 
                               id="searchInput" 
                               class="form-control" 
                               placeholder="Order ID, Name, or Phone..." 
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
>>>>>>> 81caf45 (try)
                            <?php foreach ($possible_statuses as $stat): ?>
                                <option value="<?php echo $stat; ?>" <?php echo ($status_filter == $stat) ? 'selected' : ''; ?>>
                                    <?php echo $stat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
<<<<<<< HEAD
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
=======
                    <div class="col-md-2 d-flex align-items-end">
>>>>>>> 93ac7ac (Added all)
                        <?php if(!empty($search) || !empty($status_filter)): ?>
                            <a href="admin_orders.php" class="btn btn-secondary w-100" title="Clear all filters">
                                <i class="bi bi-x-lg me-1"></i> Clear
                            </a>
<<<<<<< HEAD
                        <?php else: ?>
                            <span class="text-muted small">Showing all <?php echo $total_records; ?> orders</span>
>>>>>>> 81caf45 (try)
=======
>>>>>>> 93ac7ac (Added all)
                        <?php endif; ?>
                    </div>
                </div>
            </form>

<<<<<<< HEAD
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
=======
            <!-- Table -->
>>>>>>> 93ac7ac (Added all)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
<<<<<<< HEAD
                            <th>Actions</th>
>>>>>>> 81caf45 (try)
=======
                            <th>Action</th>
>>>>>>> 93ac7ac (Added all)
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders_result->num_rows > 0): ?>
<<<<<<< HEAD
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
=======
                            <?php while($order = $orders_result->fetch_assoc()): ?>
>>>>>>> 93ac7ac (Added all)
                            <tr>
                                <td><strong>#<?php echo $order['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                <td>
                                    <small class="text-muted">
                                        <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($order['phone_number']); ?>
                                    </small>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($order['address_detail'] . ', ' . $order['address_barangay']); ?></small>
                                </td>
                                <td>
<<<<<<< HEAD
                                    <span class="status-badge-modern <?php echo $status_class; ?>">
                                        <i class="bi bi-<?php echo $status_icon; ?>"></i>
                                        <?php echo $status; ?>
                                    </span>
>>>>>>> 81caf45 (try)
=======
                                    <small><?php echo date('M d, Y', strtotime($order['order_date'])); ?></small>
                                    <br>
                                    <small class="text-muted"><?php echo date('h:i A', strtotime($order['order_date'])); ?></small>
                                </td>
                                <td><strong class="text-success">₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td>
                                    <?php
                                        $status = htmlspecialchars($order['status']);
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
>>>>>>> 93ac7ac (Added all)
                                </td>
                                <td>
                                    <?php
                                        $current_status = $order['status'];
                                        $is_final_state = in_array($current_status, ['Completed', 'Cancelled']);
                                    ?>

                                    <?php if ($is_final_state): ?>
<<<<<<< HEAD
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
=======
                                        <span class="text-muted fst-italic small">
                                            <i class="bi bi-check-circle me-1"></i>Finalized
>>>>>>> 93ac7ac (Added all)
                                        </span>
                                    <?php else: ?>
                                        <select class="form-select form-select-sm"
                                                onchange="updateOrderStatus(<?php echo $order['id']; ?>, this.value)">

                                            <option value="<?php echo $current_status; ?>" selected disabled hidden>
                                                <?php echo $current_status; ?> ▼
                                            </option>

                                            <?php if ($current_status == 'Pending'): ?>
                                                <option value="Confirmed">Confirm Order</option>
                                            <?php elseif ($current_status == 'Confirmed'): ?>
                                                <option value="Confirmed" disabled>⏳ Awaiting Pickup...</option>
                                            <?php elseif ($current_status == 'Picked Up'): ?>
                                                <option value="On the Way">🚚 Out for Delivery</option>
                                            <?php elseif ($current_status == 'On the Way'): ?>
                                                <option value="On the Way" disabled>⏳ Awaiting Completion...</option>
                                            <?php endif; ?>
                                            
                                            <?php if (!$is_final_state): ?>
                                                <option value="Cancelled">Cancel Order</option>
                                            <?php endif; ?>
                                        </select>
                                        <?php if (in_array($current_status, ['Confirmed', 'On the Way'])): ?>
                                            <small class="text-muted d-block mt-1">
                                                <i class="bi bi-clock-history me-1"></i>User action needed
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
=======
                                <td colspan="8">
                                    <div class="empty-state">
>>>>>>> 93ac7ac (Added all)
                                        <i class="bi bi-inbox"></i>
                                        <p class="text-muted mb-0">
                                            <?php if(!empty($search) || !empty($status_filter)): ?>
                                                No orders found matching your filters.
                                            <?php else: ?>
                                                No orders available yet.
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
=======
            <!-- Pagination - Centered like the image -->
            <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-center mt-4 pt-3 border-top">
                    <nav aria-label="Order pagination">
                        <ul class="pagination mb-0">
                            <?php echo generate_order_pagination_links($page, $total_pages, $search, $status_filter); ?>
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
>>>>>>> 93ac7ac (Added all)
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
</div>

<script>
// Auto-search with debounce for accurate results
let searchTimeout;
const searchInput = document.getElementById('searchInput');

if (searchInput) {
    searchInput.addEventListener('input', function() {
        // Clear the previous timeout
        clearTimeout(searchTimeout);
        
        // Set a new timeout to submit after user stops typing (500ms delay)
        searchTimeout = setTimeout(function() {
            document.getElementById('filterForm').submit();
        }, 500); // Wait 500ms after user stops typing
    });
}

function updateOrderStatus(orderId, newStatus) {
    if (newStatus === 'Cancelled') {
        if (!confirm('⚠️ Are you sure you want to cancel Order #' + orderId + '?\n\nThis action cannot be undone.')) {
            let selectElement = event.target;
            selectElement.value = selectElement.options[0].value;
            return;
        }
    }

    // Show loading state
    const selectElement = event.target;
    const originalHTML = selectElement.innerHTML;
    selectElement.disabled = true;
    selectElement.innerHTML = '<option>Updating...</option>';

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
<<<<<<< HEAD
            selectElement.style.opacity = '1';
>>>>>>> 81caf45 (try)
=======
            selectElement.innerHTML = originalHTML;
>>>>>>> 93ac7ac (Added all)
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
        selectElement.innerHTML = originalHTML;
    });
}
</script>

<?php 
$stmt_orders->close();
>>>>>>> 81caf45 (try)
include 'admin_footer.php'; 
?>