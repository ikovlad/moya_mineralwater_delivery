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
    // Check if search term is numeric to possibly search by ID
    $id_search_clause = is_numeric($search) ? "o.id = ?" : "1=0"; // 1=0 ensures it doesn't match if not numeric
    
    $where_clauses[] = "($id_search_clause OR u.full_name LIKE ? OR u.phone_number LIKE ?)";
    
    if (is_numeric($search)) {
        $params[] = (int)$search; // Add ID param first if numeric
        $param_types .= "i";
    }
    
    $search_term_like = "%{$search}%";
    $params[] = $search_term_like; // Name param
    $params[] = $search_term_like; // Phone param
    $param_types .= "ss";
}


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
}
if (!empty($params)) {
    $stmt_orders->bind_param($param_types, ...$params);
}
$stmt_orders->execute();
$orders_result = $stmt_orders->get_result();

// Possible order statuses for the filter dropdown
$possible_statuses = ['Pending', 'Confirmed', 'Picked Up', 'On the Way', 'Completed', 'Cancelled'];

// Check for success/error messages from session (if needed, e.g., from update_order_status.php)
$alert_message = '';
if (isset($_SESSION['alert_message'])) {
    $alert_type = $_SESSION['alert_type'] ?? 'info';
    $alert_message = '<div class="alert alert-' . $alert_type . ' alert-dismissible fade show" role="alert">
        ' . htmlspecialchars($_SESSION['alert_message']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    unset($_SESSION['alert_message']);
    unset($_SESSION['alert_type']);
}

// Function to generate pagination links (keeping filters)
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
        $links .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . http_build_query($query_params) . '">Previous</a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }

    // Next Button
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $query_params['page'] = $next_page;
        $links .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?' . http_build_query($query_params) . '">Next</a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }

    return $links;
}
?>
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
                            <a href="admin_orders.php" class="btn btn-secondary"><i class="bi bi-x-lg"></i> Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

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
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders_result->num_rows > 0): ?>
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
                                </td>
                                <td>
                                    <?php
                                        $current_status = $order['status'];
                                        $is_final_state = in_array($current_status, ['Completed', 'Cancelled']);
                                    ?>

                                    <?php if ($is_final_state): ?>
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
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted p-4">
                                    <?php if(!empty($search) || !empty($status_filter)): ?>
                                        No orders found matching your filters.
                                    <?php else: ?>
                                        No orders found.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

             <?php if ($total_pages > 1): ?>
                <nav aria-label="Order navigation">
                    <ul class="pagination justify-content-center">
                        <?php echo generate_order_pagination_links($page, $total_pages, $search, $status_filter); ?>
                    </ul>
                </nav>
            <?php endif; ?>

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

    fetch('update_order_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `order_id=${orderId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Instead of full reload, maybe just update UI elements if needed,
            // but reload is simpler for now to reflect changes everywhere.
            location.reload(); 
        } else {
            alert('Failed to update status: ' + (data.message || 'Unknown error'));
             // Optionally reload even on failure to reset the dropdown
             // location.reload(); 
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while trying to update the status.');
        // location.reload(); // Optionally reload on network error
    });
}
</script>
<?php 
$stmt_orders->close(); // Close the prepared statement
include 'admin_footer.php'; 
?>