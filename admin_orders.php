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
    $id_search_clause = is_numeric($search) ? "o.id = ?" : "1=0";
    
    $where_clauses[] = "($id_search_clause OR u.full_name LIKE ? OR u.phone_number LIKE ?)";
    
    if (is_numeric($search)) {
        $params[] = (int)$search;
        $param_types .= "i";
    }
    
    $search_term_like = "%{$search}%";
    $params[] = $search_term_like;
    $params[] = $search_term_like;
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
}
if (!empty($params)) {
    $stmt_orders->bind_param($param_types, ...$params);
}
$stmt_orders->execute();
$orders_result = $stmt_orders->get_result();

// Possible order statuses for the filter dropdown
$possible_statuses = ['Pending', 'Confirmed', 'Picked Up', 'On the Way', 'Completed', 'Cancelled'];

// Check for success/error messages from session
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

// Enhanced pagination function
function generate_order_pagination_links($current_page, $total_pages, $search_value, $status_value) {
    $links = '';
    $base_url = "admin_orders.php";
    $query_params = [];
    if (!empty($search_value)) $query_params['search'] = $search_value;
    if (!empty($status_value)) $query_params['status'] = $status_value;
    
    // Calculate page range
    $range = 2;
    $start_page = max(1, $current_page - $range);
    $end_page = min($total_pages, $current_page + $range);
    
    // Previous Button
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

    // Next Button
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

/* Enhanced Pagination */
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

/* Modal Enhancements */
.modal-content {
    border: none;
    border-radius: 1rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-header {
    border-bottom: 2px solid #fee2e2;
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border-radius: 1rem 1rem 0 0;
    padding: 1.5rem;
}

.modal-body {
    padding: 2rem 1.5rem;
}

.modal-footer {
    border-top: 2px solid #e5e7eb;
    padding: 1rem 1.5rem;
}

.warning-icon {
    font-size: 4rem;
    color: #dc2626;
    margin-bottom: 1rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
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
                            <?php foreach ($possible_statuses as $stat): ?>
                                <option value="<?php echo $stat; ?>" <?php echo ($status_filter == $stat) ? 'selected' : ''; ?>>
                                    <?php echo $stat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <?php if(!empty($search) || !empty($status_filter)): ?>
                            <a href="admin_orders.php" class="btn btn-secondary w-100" title="Clear all filters">
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
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders_result->num_rows > 0): ?>
                            <?php while($order = $orders_result->fetch_assoc()): ?>
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
                                </td>
                                <td>
                                    <?php
                                        $current_status = $order['status'];
                                        $is_final_state = in_array($current_status, ['Completed', 'Cancelled']);
                                    ?>

                                    <?php if ($is_final_state): ?>
                                        <span class="text-muted fst-italic small">
                                            <i class="bi bi-check-circle me-1"></i>Finalized
                                        </span>
                                    <?php else: ?>
                                        <select class="form-select form-select-sm"
                                                onchange="handleStatusChange(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['full_name']); ?>', <?php echo $order['total_amount']; ?>, this.value, this)">

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
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p class="text-muted mb-0">
                                            <?php if(!empty($search) || !empty($status_filter)): ?>
                                                No orders found matching your filters.
                                            <?php else: ?>
                                                No orders available yet.
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
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
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Cancel Order Confirmation Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger" id="cancelModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Cancel Order Confirmation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="warning-icon">
                    <i class="bi bi-exclamation-circle-fill"></i>
                </div>
                <h3 class="mb-3">Are you sure?</h3>
                <div class="alert alert-danger mb-3">
                    <p class="mb-2"><strong>Order Details:</strong></p>
                    <p class="mb-1">Order ID: <strong>#<span id="cancelOrderId"></span></strong></p>
                    <p class="mb-1">Customer: <strong><span id="cancelCustomerName"></span></strong></p>
                    <p class="mb-0">Total: <strong>₱<span id="cancelOrderTotal"></span></strong></p>
                </div>
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>Warning:</strong> This action cannot be undone. The customer will be notified, and the order will be permanently marked as cancelled.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi me-1"></i>Keep Order
                </button>
                <button type="button" class="btn btn-danger" id="confirmCancelBtn">
                    <i class="bi me-1"></i>Cancel Order
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-search with debounce
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

// Global variables for cancel confirmation
let pendingOrderId = null;
let pendingNewStatus = null;
let pendingSelectElement = null;

function handleStatusChange(orderId, customerName, orderTotal, newStatus, selectElement) {
    if (newStatus === 'Cancelled') {
        // Show confirmation modal for cancellation
        pendingOrderId = orderId;
        pendingNewStatus = newStatus;
        pendingSelectElement = selectElement;
        
        // Populate modal with order details
        document.getElementById('cancelOrderId').textContent = orderId;
        document.getElementById('cancelCustomerName').textContent = customerName;
        document.getElementById('cancelOrderTotal').textContent = orderTotal.toFixed(2);
        
        // Show the modal
        const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
        cancelModal.show();
        
        // Reset select to original value
        selectElement.value = selectElement.options[0].value;
    } else {
        // For non-cancel status changes, proceed directly
        updateOrderStatus(orderId, newStatus, selectElement);
    }
}

// Handle confirmation button click
document.getElementById('confirmCancelBtn').addEventListener('click', function() {
    if (pendingOrderId && pendingNewStatus && pendingSelectElement) {
        // Close the modal
        const cancelModal = bootstrap.Modal.getInstance(document.getElementById('cancelModal'));
        cancelModal.hide();
        
        // Proceed with the update
        updateOrderStatus(pendingOrderId, pendingNewStatus, pendingSelectElement);
        
        // Clear pending variables
        pendingOrderId = null;
        pendingNewStatus = null;
        pendingSelectElement = null;
    }
});

// Reset pending variables when modal is closed without confirmation
document.getElementById('cancelModal').addEventListener('hidden.bs.modal', function() {
    if (pendingSelectElement) {
        pendingSelectElement.value = pendingSelectElement.options[0].value;
    }
    pendingOrderId = null;
    pendingNewStatus = null;
    pendingSelectElement = null;
});

function updateOrderStatus(orderId, newStatus, selectElement) {
    // Show loading state
    const originalHTML = selectElement.innerHTML;
    selectElement.disabled = true;
    selectElement.innerHTML = '<option>Updating...</option>';

    fetch('update_order_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `order_id=${orderId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert('❌ Failed to update status: ' + (data.message || 'Unknown error'));
            selectElement.disabled = false;
            selectElement.innerHTML = originalHTML;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ An error occurred while updating the status.');
        selectElement.disabled = false;
        selectElement.innerHTML = originalHTML;
    });
}
</script>

<?php 
$stmt_orders->close();
include 'admin_footer.php'; 
?>