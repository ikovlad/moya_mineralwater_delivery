<?php
// Initialize the session and include config
session_start();
require_once "config.php"; // Assuming config.php is in the same directory

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.html");
    exit;
}

$user_id = $_SESSION["id"];
$user_name = htmlspecialchars($_SESSION["full_name"]);
$user_barangay = htmlspecialchars($_SESSION["address_barangay"]);

// Check for success/error messages from other scripts
$profile_message = '';
if (isset($_SESSION['profile_message'])) {
    $message_type = $_SESSION['message_type'] ?? 'info';
    $profile_message = '<div class="alert alert-' . $message_type . ' alert-dismissible fade show" role="alert">
        ' . htmlspecialchars($_SESSION['profile_message']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    unset($_SESSION['profile_message']);
    unset($_SESSION['message_type']);
}

// Fetch User's Full Details
$user_details = [];
$sql_user = "SELECT full_name, email, phone_number, address_barangay, address_detail FROM users WHERE id = ?";
if ($stmt_user = mysqli_prepare($conn, $sql_user)) {
    mysqli_stmt_bind_param($stmt_user, "i", $user_id);
    if (mysqli_stmt_execute($stmt_user)) {
        $result_user = mysqli_stmt_get_result($stmt_user);
        if ($row = mysqli_fetch_assoc($result_user)) {
            $user_details = $row;
        }
        mysqli_free_result($result_user);
    }
    mysqli_stmt_close($stmt_user);
}

// Fetch User's Order History
$order_history = [];
$sql_orders = "SELECT id, order_date, total_amount, status FROM orders WHERE user_id = ? ORDER BY order_date DESC";
if ($stmt_orders = mysqli_prepare($conn, $sql_orders)) {
    mysqli_stmt_bind_param($stmt_orders, "i", $user_id);
    if (mysqli_stmt_execute($stmt_orders)) {
        $result_orders = mysqli_stmt_get_result($stmt_orders);
        while ($row = mysqli_fetch_assoc($result_orders)) {
            $order_history[] = $row;
        }
        mysqli_free_result($result_orders);
    }
    mysqli_stmt_close($stmt_orders);
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moya - My Profile & Orders</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --moya-primary: #008080; --moya-secondary: #00bfff; }
        body { font-family: 'Inter', sans-serif; background-color: #f5fcfc; }
        .bg-primary { background-color: var(--moya-primary) !important; }
        .text-primary { color: var(--moya-primary) !important; }
        .card-shadow { box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); }
        .status-badge-pending { background-color: #ffc107; color: #343a40; }
        .status-badge-confirmed { background-color: #0dcaf0; color: #000; }
        .status-badge-ontheway { background-color: #0d6efd; color: #fff; }
        .status-badge-delivered { background-color: #198754; color: #fff; }
        .status-badge-completed { background-color: #20c997; color: #fff; }
        .status-badge-cancelled { background-color: #dc3545; color: #fff; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="display-5 text-primary fw-bold">My Moya Profile</h1>
                <div>
                    <a href="order.php" class="btn btn-outline-primary me-2"><i class="bi bi-cart3 me-1"></i> New Order</a>
                    <a href="logout.php" class="btn btn-danger">Log Out</a>
                </div>
            </div>
            
            <?php echo $profile_message; ?>

            <div class="card p-4 rounded-4 card-shadow border-0 mb-5">
                <h2 class="h4 fw-bold mb-3 border-bottom pb-2">Account Information</h2>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2"><span class="fw-semibold text-secondary">Full Name:</span> <span class="fw-medium text-dark"><?php echo htmlspecialchars($user_details['full_name'] ?? $user_name); ?></span></p>
                        <p class="mb-2"><span class="fw-semibold text-secondary">Email:</span> <span class="fw-medium text-dark"><?php echo htmlspecialchars($user_details['email'] ?? 'N/A'); ?></span></p>
                        <p class="mb-2"><span class="fw-semibold text-secondary">Phone:</span> <span class="fw-medium text-dark"><?php echo htmlspecialchars($user_details['phone_number'] ?? 'N/A'); ?></span></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2"><span class="fw-semibold text-secondary">Barangay:</span> <span class="fw-medium text-dark text-primary"><?php echo htmlspecialchars($user_details['address_barangay'] ?? $user_barangay); ?></span></p>
                        <p class="mb-2"><span class="fw-semibold text-secondary">Address Details:</span> <span class="fw-medium text-dark"><?php echo htmlspecialchars($user_details['address_detail'] ?? 'N/A'); ?></span></p>
                        <p class="mb-2 small text-muted">You can request an address update by contacting us.</p>
                    </div>
                </div>
            </div>

            <div class="card p-4 rounded-4 card-shadow border-0">
                <h2 class="h4 fw-bold mb-3 border-bottom pb-2">Order History (<?php echo count($order_history); ?> Orders)</h2>
                <?php if (empty($order_history)): ?>
                    <div class="alert alert-info text-center mt-3 mb-0">You haven't placed any orders yet. <a href="order.php" class="alert-link fw-bold">Click here to start your first order!</a></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_history as $order): ?>
                                    <tr>
                                        <th scope="row">#<?php echo htmlspecialchars($order['id']); ?></th>
                                        <td><?php echo date("M d, Y, h:i A", strtotime(htmlspecialchars($order['order_date']))); ?></td>
                                        <td class="fw-bold text-success">₱<?php echo number_format(htmlspecialchars($order['total_amount']), 2); ?></td>
                                        <td>
                                            <?php 
                                                $status = htmlspecialchars($order['status']);
                                                $badge_class = '';
                                                switch ($status) {
                                                    case 'Pending': $badge_class = 'status-badge-pending'; break;
                                                    case 'Confirmed': $badge_class = 'status-badge-confirmed'; break;
                                                    case 'On the Way': $badge_class = 'status-badge-ontheway'; break;
                                                    case 'Delivered': $badge_class = 'status-badge-delivered'; break;
                                                    case 'Completed': $badge_class = 'status-badge-completed'; break;
                                                    case 'Cancelled': $badge_class = 'status-badge-cancelled'; break;
                                                    default: $badge_class = 'bg-secondary';
                                                }
                                            ?>
                                            <span class="badge rounded-pill <?php echo $badge_class; ?> p-2"><?php echo $status; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <!-- NEW LOGIC: This now handles both actions based on the order status -->
                                            <?php if ($order['status'] == 'On the Way'): ?>
                                                <form action="confirm_delivery.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="bi bi-truck"></i> Mark as Received
                                                    </button>
                                                </form>
                                            <?php elseif ($order['status'] == 'Delivered'): ?>
                                                <form action="user_confirm_completion.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-check2-circle"></i> Confirm Order
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>