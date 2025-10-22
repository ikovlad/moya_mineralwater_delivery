<?php
require_once "config.php";
include 'admin_header.php';

// Fetch users and admins
$users_result = $conn->query("SELECT id, full_name, email, phone_number, created_at FROM users ORDER BY created_at DESC");
$admins_result = $conn->query("SELECT id, full_name, email, created_at FROM admins ORDER BY created_at DESC");

// Check for success/error messages from the session
$alert_message = '';
if (isset($_SESSION['alert_message'])) {
    $alert_type = $_SESSION['alert_type'] ?? 'info';
    $alert_message = '<div class="alert alert-' . $alert_type . ' alert-dismissible fade show" role="alert">
        ' . htmlspecialchars($_SESSION['alert_message']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    // Unset the session variables so the message doesn't show again
    unset($_SESSION['alert_message']);
    unset($_SESSION['alert_type']);
}
?>

<div class="container-fluid">
    <h1 class="page-header">User Management</h1>
    
    <?php echo $alert_message; ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-people-fill"></i> Customers
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Registered On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editUserModal"
                                    data-id="<?php echo $user['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                    data-phone="<?php echo htmlspecialchars($user['phone_number']); ?>">
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-person-badge-fill"></i> Administrators
        </div>
        <div class="card-body">
             <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
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
                                $isDisabled = ($admin['id'] == $_SESSION['admin_id']) ? 'disabled' : '';
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
                    </tbody>
                </table>
            </div>
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
                <input type="text" class="form-control" id="edit-user-phone" name="phone_number" required>
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
            <p class="small text-muted">Password must be changed by the admin themselves.</p>
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
// Wait for the document to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    
    // --- JavaScript for Edit User Modal ---
    var editUserModal = document.getElementById('editUserModal');
    editUserModal.addEventListener('show.bs.modal', function (event) {
        // Button that triggered the modal
        var button = event.relatedTarget;
        
        // Extract info from data-* attributes
        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name');
        var email = button.getAttribute('data-email');
        var phone = button.getAttribute('data-phone');
        
        // Update the modal's content
        var modalTitle = editUserModal.querySelector('.modal-title');
        var idInput = editUserModal.querySelector('#edit-user-id');
        var nameInput = editUserModal.querySelector('#edit-user-name');
        var emailInput = editUserModal.querySelector('#edit-user-email');
        var phoneInput = editUserModal.querySelector('#edit-user-phone');
        
        modalTitle.textContent = 'Edit Customer #' + id;
        idInput.value = id;
        nameInput.value = name;
        emailInput.value = email;
        phoneInput.value = phone;
    });
    
    // --- JavaScript for Edit Admin Modal ---
    var editAdminModal = document.getElementById('editAdminModal');
    editAdminModal.addEventListener('show.bs.modal', function (event) {
        // Button that triggered the modal
        var button = event.relatedTarget;
        
        // Extract info from data-* attributes
        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name');
        var email = button.getAttribute('data-email');
        
        // Update the modal's content
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
        // Button that triggered the modal
        var button = event.relatedTarget;
        
        // Extract info from data-* attributes
        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name');
        var type = button.getAttribute('data-type');
        
        // Update the modal's content
        var nameSpan = deleteModal.querySelector('#delete-name');
        var confirmLink = deleteModal.querySelector('#delete-confirm-link');
        
        nameSpan.textContent = name + ' (ID: ' + id + ')';
        // Build the delete URL for our PHP script
        confirmLink.href = 'admin_manage_user.php?action=delete&user_type=' + type + '&id=' + id;
    });
    
});
</script>

<?php include 'admin_footer.php'; ?>