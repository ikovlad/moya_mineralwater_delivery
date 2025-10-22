<?php
// Start session
session_start();
require_once "config.php";

// 1. --- VALIDATION ---
// Redirect if not logged in or if the unique order ID is not in the session.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['order_success_id'])) {
    header("location: order.php");
    exit;
}

// 2. --- FETCH CONFIRMED ORDER DETAILS FROM DATABASE ---
$order_id = $_SESSION['order_success_id'];
$user_name = htmlspecialchars($_SESSION["full_name"]) ?? 'Client';
$order_total = 0.00;
$item_count = 0;

// A. Get the total amount from the `orders` table
$sql_order = "SELECT total_amount FROM orders WHERE id = ? AND user_id = ?";
if ($stmt_order = mysqli_prepare($conn, $sql_order)) {
    mysqli_stmt_bind_param($stmt_order, "ii", $order_id, $_SESSION['id']);
    if (mysqli_stmt_execute($stmt_order)) {
        $result = mysqli_stmt_get_result($stmt_order);
        if ($row = mysqli_fetch_assoc($result)) {
            $order_total = $row['total_amount'];
        }
    }
    mysqli_stmt_close($stmt_order);
}

// B. Get the sum of all item quantities from the `order_items` table
$sql_items = "SELECT SUM(quantity) as total_items FROM order_items WHERE order_id = ?";
if ($stmt_items = mysqli_prepare($conn, $sql_items)) {
    mysqli_stmt_bind_param($stmt_items, "i", $order_id);
    if (mysqli_stmt_execute($stmt_items)) {
        $result = mysqli_stmt_get_result($stmt_items);
        if ($row = mysqli_fetch_assoc($result)) {
            $item_count = $row['total_items'];
        }
    }
    mysqli_stmt_close($stmt_items);
}

// 3. --- CLEAN UP SESSION ---
// Clear the success ID from the session so this page can't be refreshed with the same message.
unset($_SESSION['order_success_id']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Moya - Order Confirmed</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { 
            --moya-primary: #008080; /* Teal */
            --moya-light: #f5fcfc;
        }
        body { background-color: var(--moya-light); font-family: 'Inter', sans-serif; }
        .container { margin-top: 50px; }
        .success-card { 
            border-left: 5px solid var(--moya-primary);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.07);
        }
        .btn-primary { 
            background-color: var(--moya-primary); 
            border-color: var(--moya-primary);
        }
        .btn-primary:hover {
            background-color: #006666; /* Darker teal */
            border-color: #006666;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-5 success-card rounded-4">
                <h1 class="card-title mb-4 text-center" style="color: var(--moya-primary);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-check-circle-fill me-2" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.42 10.32l-.99-1.04a.75.75 0 1 0-1.09 1.05l1.5 1.5a.75.75 0 0 0 1.08-.022L12.55 6.05a.75.75 0 0 0-.012-1.08z"/>
                    </svg>
                    Order Confirmed!
                </h1>
                
                <p class="lead text-center">Hello <strong><?php echo $user_name; ?></strong>, your order #<?php echo $order_id; ?> has been successfully placed.</p>
                
                <div class="text-center mt-3 mb-4">
                    <span class="badge bg-success fs-5 fw-bold p-3">
                        Total Items: <?php echo $item_count; ?>
                    </span>
                </div>

                <div class="alert text-center fw-bold fs-3 mb-4" style="background-color: #e6ffff; color: #008080;">
                    Grand Total: ₱<?php echo number_format($order_total, 2); ?>
                </div>

                <p class="text-muted text-center">
                    We are now processing your request. Please check your profile for updates on your order status.
                </p>
                
                <div class="d-flex justify-content-center gap-3 mt-4">
                    <a href="order.php" class="btn btn-outline-secondary w-50">Place Another Order</a>
                    <a href="profile.php" class="btn btn-primary w-50">View Order Status</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>