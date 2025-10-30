<?php
require_once "config.php";
include 'admin_header.php';

// --- CONFIGURATION ---
$records_per_page = 10;

// --- CUSTOMER DATA (Pagination & Filter) ---
$page_user = isset($_GET['page_user']) && is_numeric($_GET['page_user']) ? (int)$_GET['page_user'] : 1;
$search_user = isset($_GET['search_user']) ? trim($_GET['search_user']) : '';
$offset_user = ($page_user - 1) * $records_per_page;

// Build User Query
$user_params = [];
$user_where_clause = '';
if (!empty($search_user)) {
    $user_where_clause = " WHERE (full_name LIKE ? OR email LIKE ?)";
    $search_term_user = "%{$search_user}%";
    $user_params[] = $search_term_user;
    $user_params[] = $search_term_user;
}

// Get Total User Count (for pagination)
$count_sql_user = "SELECT COUNT(id) FROM users" . $user_where_clause;
$stmt_count_user = $conn->prepare($count_sql_user);
if (!empty($user_params)) {
    $stmt_count_user->bind_param(str_repeat('s', count($user_params)), ...$user_params);
}
$stmt_count_user->execute();
$stmt_count_user->bind_result($total_records_user);
$stmt_count_user->fetch();
$stmt_count_user->close();
$total_pages_user = ceil($total_records_user / $records_per_page);

// Fetch Paginated Users
$sql_user = "SELECT id, full_name, email, phone_number, address_barangay, address_detail, created_at
             FROM users" . $user_where_clause . "
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?";
$user_params[] = $records_per_page;
$user_params[] = $offset_user;

$stmt_user = $conn->prepare($sql_user);
$user_types = (empty($user_where_clause) ? '' : str_repeat('s', count($user_params) - 2)) . 'ii';
$stmt_user->bind_param($user_types, ...$user_params);
$stmt_user->execute();
$users_result = $stmt_user->get_result();

// --- ADMIN DATA (Pagination & Filter) ---
$page_admin = isset($_GET['page_admin']) && is_numeric($_GET['page_admin']) ? (int)$_GET['page_admin'] : 1;
$search_admin = isset($_GET['search_admin']) ? trim($_GET['search_admin']) : '';
$offset_admin = ($page_admin - 1) * $records_per_page;

// Build Admin Query
$admin_params = [];
$admin_where_clause = '';
if (!empty($search_admin)) {
    $admin_where_clause = " WHERE (full_name LIKE ? OR email LIKE ?)";
    $search_term_admin = "%{$search_admin}%";
    $admin_params[] = $search_term_admin;
    $admin_params[] = $search_term_admin;
}

// Get Total Admin Count (for pagination)
$count_sql_admin = "SELECT COUNT(id) FROM admins" . $admin_where_clause;
$stmt_count_admin = $conn->prepare($count_sql_admin);
if (!empty($admin_params)) {
    $stmt_count_admin->bind_param(str_repeat('s', count($admin_params)), ...$admin_params);
}
$stmt_count_admin->execute();
$stmt_count_admin->bind_result($total_records_admin);
$stmt_count_admin->fetch();
$stmt_count_admin->close();
$total_pages_admin = ceil($total_records_admin / $records_per_page);

// Fetch Paginated Admins
$sql_admin = "SELECT id, full_name, email, created_at
              FROM admins" . $admin_where_clause . "
              ORDER BY created_at DESC
              LIMIT ? OFFSET ?";
$admin_params[] = $records_per_page;
$admin_params[] = $offset_admin;

$stmt_admin = $conn->prepare($sql_admin);
$admin_types = (empty($admin_where_clause) ? '' : str_repeat('s', count($admin_params) - 2)) . 'ii';
$stmt_admin->bind_param($admin_types, ...$admin_params);
$stmt_admin->execute();
$admins_result = $stmt_admin->get_result();

// Check for success/error messages from the session
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
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    unset($_SESSION['alert_message']);
    unset($_SESSION['alert_type']);
}

// Reusable function to generate pagination links
function generate_pagination_links($current_page, $total_pages, $page_param, $search_param, $search_value) {
    $links = '';
    $base_url = "admin_users.php";
    $query_params = [];
    if (isset($_GET['page_user']) && $page_param != 'page_user') $query_params['page_user'] = $_GET['page_user'];
    if (isset($_GET['search_user']) && $search_param != 'search_user') $query_params['search_user'] = $_GET['search_user'];
    if (isset($_GET['page_admin']) && $page_param != 'page_admin') $query_params['page_admin'] = $_GET['page_admin'];
    if (isset($_GET['search_admin']) && $search_param != 'search_admin') $query_params['search_admin'] = $_GET['search_admin'];

    if (!empty($search_value)) {
        $query_params[$search_param] = $search_value;
    }

    // Previous Arrow
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $temp_query_params = $query_params;
        $temp_query_params[$page_param] = $prev_page;
        $prev_url = $base_url . '?' . http_build_query($temp_query_params);
        $links .= '<li class="page-item"><a class="page-link" href="' . $prev_url . '" aria-label="Previous"><i class="bi bi-chevron-left"></i></a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left"></i></span></li>';
    }

    // Page info
    $links .= '<li class="page-item active"><span class="page-link">Page ' . $current_page . ' of ' . $total_pages . '</span></li>';

    // Next Arrow
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $temp_query_params = $query_params;
        $temp_query_params[$page_param] = $next_page;
        $next_url = $base_url . '?' . http_build_query($temp_query_params);
        $links .= '<li class="page-item"><a class="page-link" href="' . $next_url . '" aria-label="Next"><i class="bi bi-chevron-right"></i></a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-right"></i></span></li>';
    }

    return $links;
}
?>

<style>
/* Modern User Management Styles */
.page-header-modern {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #e3e6f0;
}

.page-header-modern h1 {
    font-weight: 700;
    font-size: 1.75rem;
    color: var(--moya-dark-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.page-header-modern h1 i {
    color: var(--moya-primary);
}

.stats-pills {
    display: flex;
    gap: 1rem;
}

.stat-pill {
    background: linear-gradient(135deg, rgba(0, 128, 128, 0.1) 0%, rgba(0, 128, 128, 0.05) 100%);
    padding: 0.5rem 1.25rem;
    border-radius: 2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stat-pill i {
    color: var(--moya-primary);
}

.stat-pill-label {
    font-size: 0.75rem;
    color: #858796;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-pill-value {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--moya-dark-text);
}

/* Enhanced Alert */
.alert-modern {
    border: none;
    border-radius: 0.75rem;
    padding: 1rem 1.25rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
}

/* Section Card */
.section-card-modern {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.08);
    border: none;
    margin-bottom: 2rem;
    overflow: hidden;
}

.section-card-header-modern {
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    padding: 1.5rem;
    border-bottom: 2px solid #e3e6f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-card-header-modern h5 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--moya-dark-text);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-card-header-modern h5 i {
    color: var(--moya-primary);
    font-size: 1.25rem;
}

.btn-create {
    background: linear-gradient(135deg, var(--moya-primary) 0%, #006666 100%);
    border: none;
    padding: 0.625rem 1.25rem;
    font-weight: 600;
    border-radius: 0.5rem;
    color: white;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 128, 128, 0.2);
}

.btn-create:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 128, 128, 0.3);
    color: white;
}

.section-card-body-modern {
    padding: 1.5rem;
}

/* Search Filter */
.search-filter-modern {
    background: #f8f9fc;
    padding: 1rem;
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
}

.search-filter-modern .input-group {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.search-filter-modern .form-control {
    border: 1px solid #e3e6f0;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    border-radius: 0.5rem 0 0 0.5rem;
}

.search-filter-modern .form-control:focus {
    border-color: var(--moya-primary);
    box-shadow: 0 0 0 3px rgba(0, 128, 128, 0.1);
}

.search-filter-modern .btn {
    padding: 0.75rem 1.25rem;
    font-weight: 600;
    border-radius: 0;
}

.search-filter-modern .btn-primary {
    background: var(--moya-primary);
    border: none;
}

.search-filter-modern .btn-primary:hover {
    background: #006666;
}

.search-filter-modern .btn:last-child {
    border-radius: 0 0.5rem 0.5rem 0;
}

/* Modern Table */
.table-modern {
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.table-modern thead th {
    background: #f8f9fc;
    border: none;
    color: #858796;
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem;
    border-bottom: 2px solid #e3e6f0;
}

.table-modern tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
}

.table-modern tbody tr {
    transition: all 0.2s ease;
}

.table-modern tbody tr:hover {
    background-color: #f8f9fc;
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.table-modern tbody tr:last-child td {
    border-bottom: none;
}

/* User Info Cell */
.user-info-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar-sm {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--moya-primary) 0%, #006666 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.user-details {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 600;
    color: var(--moya-dark-text);
    font-size: 0.95rem;
}

.user-email {
    font-size: 0.8rem;
    color: #858796;
}

/* Badge Styles */
.badge-modern {
    padding: 0.375rem 0.75rem;
    border-radius: 2rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-barangay {
    background: linear-gradient(135deg, rgba(54, 185, 204, 0.1) 0%, rgba(54, 185, 204, 0.05) 100%);
    color: #36b9cc;
}

/* Action Buttons */
.btn-action {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
}

.btn-action i {
    font-size: 0.875rem;
}

.btn-action-edit {
    background: linear-gradient(135deg, rgba(78, 115, 223, 0.1) 0%, rgba(78, 115, 223, 0.05) 100%);
    color: #4e73df;
    border: none;
}

.btn-action-edit:hover {
    background: #4e73df;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(78, 115, 223, 0.3);
}

.btn-action-delete {
    background: linear-gradient(135deg, rgba(231, 74, 59, 0.1) 0%, rgba(231, 74, 59, 0.05) 100%);
    color: #e74a3b;
    border: none;
}

.btn-action-delete:hover {
    background: #e74a3b;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(231, 74, 59, 0.3);
}

.btn-action-delete:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-action-delete:disabled:hover {
    transform: none;
    background: linear-gradient(135deg, rgba(231, 74, 59, 0.1) 0%, rgba(231, 74, 59, 0.05) 100%);
    color: #e74a3b;
}

/* Pagination */
.pagination-modern {
    margin: 1.5rem 0 0 0;
}

.pagination-modern .page-item .page-link {
    border: 1px solid #e3e6f0;
    color: var(--moya-dark-text);
    padding: 0.5rem 0.75rem;
    margin: 0 0.25rem;
    border-radius: 0.5rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.pagination-modern .page-item.active .page-link {
    background: linear-gradient(135deg, var(--moya-primary) 0%, #006666 100%);
    border-color: var(--moya-primary);
    color: white;
    box-shadow: 0 2px 8px rgba(0, 128, 128, 0.2);
}

.pagination-modern .page-item .page-link:hover {
    background: var(--moya-primary);
    border-color: var(--moya-primary);
    color: white;
    transform: translateY(-2px);
}

.pagination-modern .page-item.disabled .page-link {
    background: #f8f9fc;
    border-color: #e3e6f0;
    color: #cbd5e0;
}

/* Empty State */
.empty-state-modern {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-state-modern i {
    font-size: 4rem;
    color: #cbd5e0;
    margin-bottom: 1rem;
}

.empty-state-modern h5 {
    color: #858796;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.empty-state-modern p {
    color: #a0aec0;
    font-size: 0.95rem;
}

/* Modal Enhancements */
.modal-content {
    border: none;
    border-radius: 1rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

.modal-header {
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    border-bottom: 2px solid #e3e6f0;
    padding: 1.5rem;
    border-radius: 1rem 1rem 0 0;
}

.modal-header .modal-title {
    font-weight: 700;
    color: var(--moya-dark-text);
    font-size: 1.25rem;
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    border-top: 2px solid #e3e6f0;
    padding: 1.25rem 2rem;
}

/* Responsive */
@media (max-width: 768px) {
    .stats-pills {
        flex-direction: column;
    }
    
    .page-header-modern {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .user-info-cell {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .table-modern {
        font-size: 0.875rem;
    }
    
    .btn-action {
        padding: 0.375rem 0.75rem;
        font-size: 0.8rem;
    }
}
</style>

<div class="container-fluid">
    <div class="page-header-modern">
        <h1><i class="bi bi-people-fill"></i> User Management</h1>
        <div class="stats-pills">
            <div class="stat-pill">
                <div>
                    <div class="stat-pill-label">Customers</div>
                    <div class="stat-pill-value"><?php echo $total_records_user; ?></div>
                </div>
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-pill">
                <div>
                    <div class="stat-pill-label">Admins</div>
                    <div class="stat-pill-value"><?php echo $total_records_admin; ?></div>
                </div>
                <i class="bi bi-shield-check"></i>
            </div>
        </div>
    </div>

    <?php echo $alert_message; ?>

    <!-- Customers Section -->
    <div class="section-card-modern">
        <div class="section-card-header-modern">
            <h5><i class="bi bi-people-fill"></i> Customers</h5>
            <button class="btn btn-create" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="bi bi-plus-circle-fill me-2"></i>Add Customer
            </button>
        </div>
        <div class="section-card-body-modern">
            <form method="GET" action="admin_users.php" class="search-filter-modern">
                <input type="hidden" name="page_admin" value="<?php echo $page_admin; ?>">
                <?php if(!empty($search_admin)): ?>
                <input type="hidden" name="search_admin" value="<?php echo htmlspecialchars($search_admin); ?>">
                <?php endif; ?>
                <div class="input-group">
                    <input type="text" name="search_user" class="form-control" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search_user); ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search me-2"></i>Search</button>
                    <?php if(!empty($search_user)): ?>
                        <a href="admin_users.php" class="btn btn-secondary"><i class="bi bi-x-lg me-2"></i>Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Barangay</th>
                            <th>Registered</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users_result->num_rows > 0): ?>
                            <?php while ($user = $users_result->fetch_assoc()): 
                                $initials = strtoupper(substr($user['full_name'], 0, 1));
                            ?>
                            <tr>
                                <td>
                                    <div class="user-info-cell">
                                        <div class="user-avatar-sm"><?php echo $initials; ?></div>
                                        <div class="user-details">
                                            <span class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
                                            <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                <td><span class="badge-modern badge-barangay"><?php echo htmlspecialchars($user['address_barangay']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-action btn-action-edit me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editUserModal"
                                        data-id="<?php echo $user['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                        data-phone="<?php echo htmlspecialchars($user['phone_number']); ?>"
                                        data-barangay="<?php echo htmlspecialchars($user['address_barangay']); ?>"
                                        data-details="<?php echo htmlspecialchars($user['address_detail']); ?>">
                                        <i class="bi bi-pencil-fill me-1"></i>Edit
                                    </button>
                                    <button class="btn btn-action btn-action-delete"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteModal"
                                        data-id="<?php echo $user['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                        data-type="user">
                                        <i class="bi bi-trash-fill me-1"></i>Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state-modern">
                                        <i class="bi bi-inbox"></i>
                                        <h5>No Customers Found</h5>
                                        <p>Start by adding your first customer</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages_user > 1): ?>
                <nav>
                    <ul class="pagination pagination-modern justify-content-center">
                        <?php echo generate_pagination_links($page_user, $total_pages_user, 'page_user', 'search_user', $search_user); ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Administrators Section -->
    <div class="section-card-modern">
        <div class="section-card-header-modern">
            <h5><i class="bi bi-shield-check-fill"></i> Administrators</h5>
            <button class="btn btn-create" data-bs-toggle="modal" data-bs-target="#createAdminModal">
                <i class="bi bi-plus-circle-fill me-2"></i>Add Admin
            </button>
        </div>
        <div class="section-card-body-modern">
            <form method="GET" action="admin_users.php" class="search-filter-modern">
                <input type="hidden" name="page_user" value="<?php echo $page_user; ?>">
                 <?php if(!empty($search_user)): ?>
                <input type="hidden" name="search_user" value="<?php echo htmlspecialchars($search_user); ?>">
                <?php endif; ?>
                <div class="input-group">
                    <input type="text" name="search_admin" class="form-control" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search_admin); ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search me-2"></i>Search</button>
                    <?php if(!empty($search_admin)): ?>
                        <a href="admin_users.php" class="btn btn-secondary"><i class="bi bi-x-lg me-2"></i>Clear</a>
                    <?php endif; ?>
                </div>
            </form>

             <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Administrator</th>
                            <th>Joined</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($admins_result->num_rows > 0): ?>
                            <?php while ($admin = $admins_result->fetch_assoc()): 
                                $initials = strtoupper(substr($admin['full_name'], 0, 1));
                            ?>
                            <tr>
                                <td>
                                    <div class="user-info-cell">
                                        <div class="user-avatar-sm"><?php echo $initials; ?></div>
                                        <div class="user-details">
                                            <span class="user-name"><?php echo htmlspecialchars($admin['full_name']); ?></span>
                                            <span class="user-email"><?php echo htmlspecialchars($admin['email']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-action btn-action-edit me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAdminModal"
                                        data-id="<?php echo $admin['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($admin['full_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($admin['email']); ?>">
                                        <i class="bi bi-pencil-fill me-1"></i>Edit
                                    </button>

                                    <?php
                                    $isDisabled = (isset($_SESSION['admin_id']) && $admin['id'] == $_SESSION['admin_id']) ? 'disabled' : '';
                                    $title = ($isDisabled) ? 'You cannot delete your own account' : 'Delete this admin';
                                    ?>
                                    <button class="btn btn-action btn-action-delete"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteModal"
                                        data-id="<?php echo $admin['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($admin['full_name']); ?>"
                                        data-type="admin"
                                        title="<?php echo $title; ?>"
                                        <?php echo $isDisabled; ?>>
                                        <i class="bi bi-trash-fill me-1"></i>Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">
                                    <div class="empty-state-modern">
                                        <i class="bi bi-shield-x"></i>
                                        <h5>No Administrators Found</h5>
                                        <p>Add administrators to manage the system</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages_admin > 1): ?>
                <nav>
                    <ul class="pagination pagination-modern justify-content-center">
                        <?php echo generate_pagination_links($page_admin, $total_pages_admin, 'page_admin', 'search_admin', $search_admin); ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createUserModalLabel"><i class="bi bi-person-plus-fill me-2"></i>Create New Customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="admin_manage_user.php" method="POST" id="createUserForm">
        <div class="modal-body">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="user_type" value="user">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="create-user-name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="create-user-name" name="full_name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="create-user-email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="create-user-email" name="email" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="create-user-phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="create-user-phone" name="phone_number" pattern="^09\d{9}$" placeholder="09123456789" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="create-user-barangay" class="form-label">Barangay</label>
                    <select class="form-select" id="create-user-barangay" name="address_barangay" required>
                        <option value="" disabled selected>Select delivery area...</option>
                        <option value="Cataguingtingan">Cataguingtingan</option>
                        <option value="Poblacion East">Poblacion East</option>
                        <option value="Poblacion West">Poblacion West</option>
                        <option value="Subusub">Subusub</option>
                        <option value="Bani">Bani</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="create-user-details" class="form-label">Address Details (Purok/Landmark)</label>
                <textarea class="form-control" id="create-user-details" name="address_detail" rows="2" required></textarea>
            </div>

            <hr class="my-4">
            <h6 class="mb-3"><i class="bi bi-lock-fill me-2"></i>Account Security</h6>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="create-user-password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="create-user-password" name="password" minlength="8" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="create-user-confirm-password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="create-user-confirm-password" required>
                    <div class="invalid-feedback" id="createUserPasswordError">Passwords do not match.</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-create"><i class="bi bi-check-circle-fill me-2"></i>Create Customer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Create Admin Modal -->
<div class="modal fade" id="createAdminModal" tabindex="-1" aria-labelledby="createAdminModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createAdminModalLabel"><i class="bi bi-shield-plus me-2"></i>Create New Admin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="admin_manage_user.php" method="POST" id="createAdminForm">
        <div class="modal-body">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="user_type" value="admin">

            <div class="mb-3">
                <label for="create-admin-name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="create-admin-name" name="full_name" required>
            </div>
            <div class="mb-3">
                <label for="create-admin-email" class="form-label">Email</label>
                <input type="email" class="form-control" id="create-admin-email" name="email" required>
            </div>

            <hr class="my-4">
            <h6 class="mb-3"><i class="bi bi-lock-fill me-2"></i>Account Security</h6>

            <div class="mb-3">
                <label for="create-admin-password" class="form-label">Password</label>
                <input type="password" class="form-control" id="create-admin-password" name="password" minlength="8" required>
            </div>
            <div class="mb-3">
                <label for="create-admin-confirm-password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="create-admin-confirm-password" required>
                <div class="invalid-feedback" id="createAdminPasswordError">Passwords do not match.</div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-create"><i class="bi bi-check-circle-fill me-2"></i>Create Admin</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editUserModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="admin_manage_user.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_type" value="user">
            <input type="hidden" name="user_id" id="edit-user-id">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="edit-user-name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="edit-user-name" name="full_name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="edit-user-email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="edit-user-email" name="email" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="edit-user-phone" class="form-label">Phone Number</label>
                    <input type="text" class="form-control" id="edit-user-phone" name="phone_number" pattern="^09\d{9}$" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="edit-user-barangay" class="form-label">Barangay</label>
                    <select class="form-select" id="edit-user-barangay" name="address_barangay" required>
                        <option value="" disabled>Select delivery area...</option>
                        <option value="Cataguingtingan">Cataguingtingan</option>
                        <option value="Poblacion East">Poblacion East</option>
                        <option value="Poblacion West">Poblacion West</option>
                        <option value="Subusub">Subusub</option>
                        <option value="Bani">Bani</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="edit-user-details" class="form-label">Address Details (Purok/Landmark)</label>
                <textarea class="form-control" id="edit-user-details" name="address_detail" rows="2" required></textarea>
            </div>

            <hr class="my-4">
            <h6 class="mb-3"><i class="bi bi-lock-fill me-2"></i>Change Password (Optional)</h6>

            <div class="mb-3">
                <label for="edit-user-password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="edit-user-password" name="password" minlength="8">
                <div class="form-text">Leave blank to keep the current password.</div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill me-2"></i>Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editAdminModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Administrator</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="admin_manage_user.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_type" value="admin">
            <input type="hidden" name="user_id" id="edit-admin-id">

            <div class="mb-3">
                <label for="edit-admin-name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="edit-admin-name" name="full_name" required>
            </div>
            <div class="mb-3">
                <label for="edit-admin-email" class="form-label">Email</label>
                <input type="email" class="form-control" id="edit-admin-email" name="email" required>
            </div>

            <hr class="my-4">
            <h6 class="mb-3"><i class="bi bi-lock-fill me-2"></i>Change Password (Optional)</h6>

            <div class="mb-3">
                <label for="edit-admin-password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="edit-admin-password" name="password" minlength="8">
                <div class="form-text">Leave blank to keep the current password.</div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill me-2"></i>Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Deletion</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-3">Are you sure you want to delete <strong id="delete-name"></strong>?</p>
        <div class="alert alert-danger mb-0">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <strong>Warning:</strong> This action cannot be undone. All associated data will be permanently removed.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a id="delete-confirm-link" href="#" class="btn btn-danger"><i class="bi bi-trash-fill me-2"></i>Delete Permanently</a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // Edit User Modal
    var editUserModal = document.getElementById('editUserModal');
    editUserModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name');
        var email = button.getAttribute('data-email');
        var phone = button.getAttribute('data-phone');
        var barangay = button.getAttribute('data-barangay');
        var details = button.getAttribute('data-details');

        var modalTitle = editUserModal.querySelector('.modal-title');
        var idInput = editUserModal.querySelector('#edit-user-id');
        var nameInput = editUserModal.querySelector('#edit-user-name');
        var emailInput = editUserModal.querySelector('#edit-user-email');
        var phoneInput = editUserModal.querySelector('#edit-user-phone');
        var barangayInput = editUserModal.querySelector('#edit-user-barangay');
        var detailsInput = editUserModal.querySelector('#edit-user-details');

        modalTitle.innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Customer #' + id;
        idInput.value = id;
        nameInput.value = name;
        emailInput.value = email;
        phoneInput.value = phone;
        barangayInput.value = barangay;
        detailsInput.value = details;
    });

    // Edit Admin Modal
    var editAdminModal = document.getElementById('editAdminModal');
    editAdminModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name');
        var email = button.getAttribute('data-email');

        var modalTitle = editAdminModal.querySelector('.modal-title');
        var idInput = editAdminModal.querySelector('#edit-admin-id');
        var nameInput = editAdminModal.querySelector('#edit-admin-name');
        var emailInput = editAdminModal.querySelector('#edit-admin-email');

        modalTitle.innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Admin #' + id;
        idInput.value = id;
        nameInput.value = name;
        emailInput.value = email;
    });

    // Delete Modal
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name');
        var type = button.getAttribute('data-type');

        var nameSpan = deleteModal.querySelector('#delete-name');
        var confirmLink = deleteModal.querySelector('#delete-confirm-link');

        nameSpan.textContent = name + ' (ID: ' + id + ')';
        confirmLink.href = 'admin_manage_user.php?action=delete&user_type=' + type + '&id=' + id;
    });

    // Password Confirmation for Create User
    var createUserForm = document.getElementById('createUserForm');
    var createUserPassword = document.getElementById('create-user-password');
    var createUserConfirmPassword = document.getElementById('create-user-confirm-password');
    var createUserPasswordError = document.getElementById('createUserPasswordError');

    createUserForm.addEventListener('submit', function(event) {
        if (createUserPassword.value !== createUserConfirmPassword.value) {
            event.preventDefault();
            createUserConfirmPassword.classList.add('is-invalid');
            createUserPasswordError.style.display = 'block';
        } else {
            createUserConfirmPassword.classList.remove('is-invalid');
            createUserPasswordError.style.display = 'none';
        }
    });

    // Password Confirmation for Create Admin
    var createAdminForm = document.getElementById('createAdminForm');
    var createAdminPassword = document.getElementById('create-admin-password');
    var createAdminConfirmPassword = document.getElementById('create-admin-confirm-password');
    var createAdminPasswordError = document.getElementById('createAdminPasswordError');

    createAdminForm.addEventListener('submit', function(event) {
        if (createAdminPassword.value !== createAdminConfirmPassword.value) {
            event.preventDefault();
            createAdminConfirmPassword.classList.add('is-invalid');
            createAdminPasswordError.style.display = 'block';
        } else {
            createAdminConfirmPassword.classList.remove('is-invalid');
            createAdminPasswordError.style.display = 'none';
        }
    });
});
</script>

<?php
if (isset($stmt_user) && $stmt_user instanceof mysqli_stmt) { $stmt_user->close(); }
if (isset($stmt_admin) && $stmt_admin instanceof mysqli_stmt) { $stmt_admin->close(); }
include 'admin_footer.php';
?>