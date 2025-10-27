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
    // Use str_repeat to dynamically create the types string (e.g., "ss")
    $stmt_count_user->bind_param(str_repeat('s', count($user_params)), ...$user_params);
}
$stmt_count_user->execute();
$stmt_count_user->bind_result($total_records_user);
$stmt_count_user->fetch();
$stmt_count_user->close();
$total_pages_user = ceil($total_records_user / $records_per_page);

// Fetch Paginated Users
// --- FIXED: Changed address_details to address_detail ---
$sql_user = "SELECT id, full_name, email, phone_number, address_barangay, address_detail, created_at
             FROM users" . $user_where_clause . "
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?";
// Add LIMIT and OFFSET params
$user_params[] = $records_per_page;
$user_params[] = $offset_user;

$stmt_user = $conn->prepare($sql_user);
// Need to generate types string again (e.g., "ssii" if searching, "ii" if not)
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
// Need to generate types string again (e.g., "ssii" if searching, "ii" if not)
$admin_types = (empty($admin_where_clause) ? '' : str_repeat('s', count($admin_params) - 2)) . 'ii';
$stmt_admin->bind_param($admin_types, ...$admin_params);
$stmt_admin->execute();
$admins_result = $stmt_admin->get_result();


// Check for success/error messages from the session
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

// --- UPDATED FUNCTION ---
// Reusable function to generate pagination links (Arrow Style)
function generate_pagination_links($current_page, $total_pages, $page_param, $search_param, $search_value) {
    $links = '';
    $base_url = "admin_users.php";
    // Build the query string for other parameters
    $query_params = [];
    if (isset($_GET['page_user']) && $page_param != 'page_user') $query_params['page_user'] = $_GET['page_user'];
    if (isset($_GET['search_user']) && $search_param != 'search_user') $query_params['search_user'] = $_GET['search_user'];
    if (isset($_GET['page_admin']) && $page_param != 'page_admin') $query_params['page_admin'] = $_GET['page_admin'];
    if (isset($_GET['search_admin']) && $search_param != 'search_admin') $query_params['search_admin'] = $_GET['search_admin'];

    // Add the current search query
    if (!empty($search_value)) {
        $query_params[$search_param] = $search_value;
    }

    // --- Previous Arrow ---
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $temp_query_params = $query_params; // Copy params to avoid modifying original for next link
        $temp_query_params[$page_param] = $prev_page;
        $prev_url = $base_url . '?' . http_build_query($temp_query_params);
        $links .= '<li class="page-item"><a class="page-link" href="' . $prev_url . '" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link" aria-label="Previous"><span aria-hidden="true">&laquo;</span></span></li>';
    }

    // --- Current Page Number (Not clickable, just display) ---
    $links .= '<li class="page-item active" aria-current="page"><span class="page-link">' . $current_page . '</span></li>';


    // --- Next Arrow ---
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $temp_query_params = $query_params; // Copy params again
        $temp_query_params[$page_param] = $next_page;
        $next_url = $base_url . '?' . http_build_query($temp_query_params);
        $links .= '<li class="page-item"><a class="page-link" href="' . $next_url . '" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';
    } else {
        $links .= '<li class="page-item disabled"><span class="page-link" aria-label="Next"><span aria-hidden="true">&raquo;</span></span></li>';
    }

    return $links;
}
?>

<div class="container-fluid">
    <h1 class="page-header">User Management</h1>

    <?php echo $alert_message; ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-people-fill"></i> Customers</span>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="bi bi-plus-circle-fill"></i> Create New Customer
            </button>
        </div>
        <div class="card-body">
            <form method="GET" action="admin_users.php" class="mb-3">
                <input type="hidden" name="page_admin" value="<?php echo $page_admin; ?>">
                <?php if(!empty($search_admin)): ?>
                <input type="hidden" name="search_admin" value="<?php echo htmlspecialchars($search_admin); ?>">
                <?php endif; ?>
                <div class="input-group">
                    <input type="text" name="search_user" class="form-control" placeholder="Filter by name or email..." value="<?php echo htmlspecialchars($search_user); ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Filter</button>
                    <?php if(!empty($search_user)): ?>
                        <a href="admin_users.php" class="btn btn-secondary"><i class="bi bi-x-lg"></i> Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-responsive">
                <table id="usersTable" class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Barangay</th>
                            <th>Registered On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users_result->num_rows > 0): ?>
                            <?php while ($user = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($user['address_barangay']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editUserModal"
                                        data-id="<?php echo $user['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                        data-phone="<?php echo htmlspecialchars($user['phone_number']); ?>"
                                        data-barangay="<?php echo htmlspecialchars($user['address_barangay']); ?>"
                                        data-details="<?php echo htmlspecialchars($user['address_detail']); ?>">
                                        <i class="bi bi-pencil-fill"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteModal"
                                        data-id="<?php echo $user['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                        data-type="user">
                                        <i class="bi bi-trash-fill"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No customers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages_user > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php echo generate_pagination_links($page_user, $total_pages_user, 'page_user', 'search_user', $search_user); ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-person-badge-fill"></i> Administrators</span>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createAdminModal">
                <i class="bi bi-plus-circle-fill"></i> Create New Admin
            </button>
        </div>
        <div class="card-body">
            <form method="GET" action="admin_users.php" class="mb-3">
                <input type="hidden" name="page_user" value="<?php echo $page_user; ?>">
                 <?php if(!empty($search_user)): ?>
                <input type="hidden" name="search_user" value="<?php echo htmlspecialchars($search_user); ?>">
                <?php endif; ?>
                <div class="input-group">
                    <input type="text" name="search_admin" class="form-control" placeholder="Filter by name or email..." value="<?php echo htmlspecialchars($search_admin); ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Filter</button>
                    <?php if(!empty($search_admin)): ?>
                        <a href="admin_users.php" class="btn btn-secondary"><i class="bi bi-x-lg"></i> Clear</a>
                    <?php endif; ?>
                </div>
            </form>

             <div class="table-responsive">
                <table id="adminsTable" class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Created On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($admins_result->num_rows > 0): ?>
                            <?php while ($admin = $admins_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $admin['id']; ?></td>
                                <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAdminModal"
                                        data-id="<?php echo $admin['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($admin['full_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($admin['email']); ?>">
                                        <i class="bi bi-pencil-fill"></i> Edit
                                    </button>

                                    <?php
                                    // SAFETY CHECK: Disable delete button if it's the currently logged-in admin
                                    $isDisabled = (isset($_SESSION['admin_id']) && $admin['id'] == $_SESSION['admin_id']) ? 'disabled' : '';
                                    $title = ($isDisabled) ? 'You cannot delete your own account' : 'Delete this admin';
                                    ?>
                                    <button class="btn btn-sm btn-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteModal"
                                        data-id="<?php echo $admin['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($admin['full_name']); ?>"
                                        data-type="admin"
                                        title="<?php echo $title; ?>"
                                        <?php echo $isDisabled; ?>>
                                        <i class="bi bi-trash-fill"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No admins found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages_admin > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php echo generate_pagination_links($page_admin, $total_pages_admin, 'page_admin', 'search_admin', $search_admin); ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createUserModalLabel">Create New Customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="admin_manage_user.php" method="POST" id="createUserForm">
        <div class="modal-body">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="user_type" value="user">

            <div class="mb-3">
                <label for="create-user-name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="create-user-name" name="full_name" required>
            </div>
            <div class="mb-3">
                <label for="create-user-email" class="form-label">Email</label>
                <input type="email" class="form-control" id="create-user-email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="create-user-phone" class="form-label">Phone Number</label>
                <input type="tel" class="form-control" id="create-user-phone" name="phone_number" pattern="^09\d{9}$" placeholder="09123456789" required>
            </div>
            <div class="mb-3">
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
            <div class="mb-3">
                <label for="create-user-details" class="form-label">Address Details (Purok/Landmark)</label>
                <textarea class="form-control" id="create-user-details" name="address_detail" rows="2" required></textarea>
            </div>
            <hr>
            <div class="mb-3">
                <label for="create-user-password" class="form-label">Password</label>
                <input type="password" class="form-control" id="create-user-password" name="password" minlength="8" required>
            </div>
            <div class="mb-3">
                <label for="create-user-confirm-password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="create-user-confirm-password" required>
                <div class="invalid-feedback" id="createUserPasswordError">Passwords do not match.</div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Create Customer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="createAdminModal" tabindex="-1" aria-labelledby="createAdminModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createAdminModalLabel">Create New Admin</h5>
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
            <hr>
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
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Create Admin</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editUserModalLabel">Edit Customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="admin_manage_user.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_type" value="user">
            <input type="hidden" name="user_id" id="edit-user-id">

            <div class="mb-3">
                <label for="edit-user-name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="edit-user-name" name="full_name" required>
            </div>
            <div class="mb-3">
                <label for="edit-user-email" class="form-label">Email</label>
                <input type="email" class="form-control" id="edit-user-email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="edit-user-phone" class="form-label">Phone Number</label>
                <input type="text" class="form-control" id="edit-user-phone" name="phone_number" pattern="^09\d{9}$" required>
            </div>

            <div class="mb-3">
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
            <div class="mb-3">
                <label for="edit-user-details" class="form-label">Address Details (Purok/Landmark)</label>
                <textarea class="form-control" id="edit-user-details" name="address_detail" rows="2" required></textarea>
            </div>

            <hr>
            <div class="mb-3">
                <label for="edit-user-password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="edit-user-password" name="password" minlength="8">
                <div class="form-text">Leave blank to keep the current password.</div>
            </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editAdminModalLabel">Edit Administrator</h5>
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
            <hr>
            <div class="mb-3">
                <label for="edit-admin-password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="edit-admin-password" name="password" minlength="8">
                <div class="form-text">Leave blank to keep the current password.</div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete <strong id="delete-name"></strong>?</p>
        <p class="text-danger">This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a id="delete-confirm-link" href="#" class="btn btn-danger">Delete</a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // --- JavaScript for Edit User Modal ---
    var editUserModal = document.getElementById('editUserModal');
    editUserModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;

        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name');
        var email = button.getAttribute('data-email');
        var phone = button.getAttribute('data-phone');
        var barangay = button.getAttribute('data-barangay'); // ADDED
        var details = button.getAttribute('data-details');   // ADDED

        var modalTitle = editUserModal.querySelector('.modal-title');
        var idInput = editUserModal.querySelector('#edit-user-id');
        var nameInput = editUserModal.querySelector('#edit-user-name');
        var emailInput = editUserModal.querySelector('#edit-user-email');
        var phoneInput = editUserModal.querySelector('#edit-user-phone');
        var barangayInput = editUserModal.querySelector('#edit-user-barangay'); // ADDED
        var detailsInput = editUserModal.querySelector('#edit-user-details');   // ADDED

        modalTitle.textContent = 'Edit Customer #' + id;
        idInput.value = id;
        nameInput.value = name;
        emailInput.value = email;
        phoneInput.value = phone;
        barangayInput.value = barangay; // ADDED
        detailsInput.value = details;   // ADDED
    });

    // --- JavaScript for Edit Admin Modal ---
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

        modalTitle.textContent = 'Edit Admin #' + id;
        idInput.value = id;
        nameInput.value = name;
        emailInput.value = email;
    });

    // --- JavaScript for Reusable Delete Modal ---
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;

        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name');
        var type = button.getAttribute('data-type');

        var nameSpan = deleteModal.querySelector('#delete-name');
        var confirmLink = deleteModal.querySelector('#delete-confirm-link');

        nameSpan.textContent = name + ' (ID: ' + id + ')';
        // Build the delete URL for our PHP script
        confirmLink.href = 'admin_manage_user.php?action=delete&user_type=' + type + '&id=' + id;
    });

    // --- ADDED: Password Confirmation for Create User Modal ---
    var createUserForm = document.getElementById('createUserForm');
    var createUserPassword = document.getElementById('create-user-password');
    var createUserConfirmPassword = document.getElementById('create-user-confirm-password');
    var createUserPasswordError = document.getElementById('createUserPasswordError');

    createUserForm.addEventListener('submit', function(event) {
        if (createUserPassword.value !== createUserConfirmPassword.value) {
            event.preventDefault(); // Stop form submission
            createUserConfirmPassword.classList.add('is-invalid');
            createUserPasswordError.style.display = 'block';
        } else {
            createUserConfirmPassword.classList.remove('is-invalid');
            createUserPasswordError.style.display = 'none';
        }
    });

    // --- ADDED: Password Confirmation for Create Admin Modal ---
    var createAdminForm = document.getElementById('createAdminForm');
    var createAdminPassword = document.getElementById('create-admin-password');
    var createAdminConfirmPassword = document.getElementById('create-admin-confirm-password');
    var createAdminPasswordError = document.getElementById('createAdminPasswordError');

    createAdminForm.addEventListener('submit', function(event) {
        if (createAdminPassword.value !== createAdminConfirmPassword.value) {
            event.preventDefault(); // Stop form submission
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
// Close statements if they were prepared
if (isset($stmt_user) && $stmt_user instanceof mysqli_stmt) { $stmt_user->close(); }
if (isset($stmt_admin) && $stmt_admin instanceof mysqli_stmt) { $stmt_admin->close(); }

include 'admin_footer.php';
?>