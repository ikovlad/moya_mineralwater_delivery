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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --moya-primary: #008080;
            --moya-secondary: #00bfff;
            --moya-cta: #ff9900;
            --moya-bg: #f5fcfc;
        }
        body {
            font-family: Lato, sans-serif;
            background-image: url(img/bg.svg);
            color: #1f2937;
            font-size: 1.5em;
        }
        h1, h2 {
            font-family: Bricolage Grotesque, sans-serif;
        }
        h1 {
            font-size: 4rem;
        }
        h2 {
            font-size: 2.5rem;
        }
        .bg-primary { background-color: var(--moya-primary) !important; }
        .text-primary { color: var(--moya-primary) !important; }
        .card-shadow {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 10px 15px rgba(0, 0, 0, 0.03);
        }
        .btn-cta {
            background-color: #007bff;
            border-color: #007bff;
            color: #fff !important;
            font-weight: 700;
            padding: .75rem 2rem;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
        }
        .btn-cta:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            color: #fff !important;
            box-shadow: 0 10px 20px rgba(0, 123, 255, 0.5) !important;
            transform: translateY(-2px);
        }
        .status-badge-pending { background-color: #ffc107; color: #343a40; }
        .status-badge-confirmed { background-color: #0dcaf0; color: #000; }
        /* <<< ADDED Style for Picked Up */
        .status-badge-pickedup { background-color: #6f42c1; color: #fff; }
        .status-badge-ontheway { background-color: #0d6efd; color: #fff; }
        /* Removed .status-badge-delivered */
        .status-badge-completed { background-color: #198754; color: #fff; } /* <<< CHANGED Completed to success */
        .status-badge-cancelled { background-color: #dc3545; color: #fff; }
        .profile-header {
            background: linear-gradient(135deg, var(--moya-primary) 0%, var(--moya-secondary) 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 1rem;
            position: relative;
        }
        .profile-pic-container:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        .profile-pic-container:hover + .position-absolute {
            transform: scale(1.1);
        }
        .info-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        .info-value {
            font-size: 1.1rem;
            color: #1f2937;
            font-weight: 500;
        }
        .table {
            font-size: 0.95rem;
        }
        .table th {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        #profileDropdown {
            background-color: #008080 !important;
        }
        @media (max-width: 768px) {
            h1 { font-size: 2.5rem; }
            h2 { font-size: 1.8rem; }
            body { font-size: 1.2em; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
    <div class="container py-2">
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center fs-3 gap-2" href="home.php">
            <img src="img/moya_logo.png" alt="moya_logo" style="height: 50px; width: auto; object-fit: contain;">
            Moya
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 fw-semibold fs-5">
                <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="home.php#products">Containers</a></li>
                <li class="nav-item"><a class="nav-link" href="home.php#process">Delivery</a></li>
                <li class="nav-item"><a class="nav-link" href="home.php#location">Area</a></li>
                <li class="nav-item dropdown ms-lg-3">
                    <a class="nav-link dropdown-toggle btn btn-primary rounded-pill px-4 btn-md text-white" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo $user_name; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="order.php">Order Here</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger fw-semibold" href="logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="profile-header text-center card-shadow mb-4">
                <div class="container position-relative">

                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <div class="position-relative">
                            <label for="profilePictureInput" style="cursor: pointer;">
                                <div class="bg-white rounded-circle p-3 profile-pic-container" style="width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                                    <i class="bi bi-person-fill text-primary" style="font-size: 3rem;"></i>
                                </div>
                                <div class="position-absolute bottom-0 end-0 bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; border: 3px solid white;">
                                    <i class="bi bi-camera-fill text-white" style="font-size: 0.9rem;"></i>
                                </div>
                            </label>
                            <input type="file" id="profilePictureInput" accept="image/*" style="display: none;" onchange="handleProfilePictureUpload(this)">
                        </div>
                    </div>
                    <h1 class="display-5 fw-bold mb-2" style="color: white;"><?php echo htmlspecialchars($user_details['full_name'] ?? $user_name); ?></h1>
                    <p class="lead mb-0" style="color: rgba(255,255,255,0.9);">
                        <i class="bi bi-geo-alt-fill me-2"></i><?php echo htmlspecialchars($user_details['address_barangay'] ?? $user_barangay); ?>
                    </p>
                </div>
            </div>

            <?php echo $profile_message; ?>

            <div class="card p-4 rounded-4 card-shadow border-0 mb-5">
                <h2 class="fw-bold mb-4 pb-3 border-bottom">
                    <i class="bi bi-person-badge me-2 text-primary"></i> Account Information
                </h2>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($user_details['full_name'] ?? $user_name); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($user_details['email'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="info-label">Phone Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($user_details['phone_number'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="info-label">Delivery Barangay</div>
                            <div class="info-value text-primary fw-bold"><?php echo htmlspecialchars($user_details['address_barangay'] ?? $user_barangay); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="info-label">Complete Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($user_details['address_detail'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="alert alert-info border-0 mt-3 mb-0" style="font-size: 0.85rem;">
                            <i class="bi bi-info-circle me-2"></i>
                            Need to update your address? Please contact our support team.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-4 rounded-4 card-shadow border-0 mb-5">
                <h2 class="fw-bold mb-4 pb-3 border-bottom">
                    <i class="bi bi-clock-history me-2 text-primary"></i> Order History
                    <span class="badge bg-primary rounded-pill ms-2"><?php echo count($order_history); ?></span>
                </h2>
                <?php if (empty($order_history)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-cart-x text-muted" style="font-size: 4rem;"></i>
                        <h3 class="mt-3 text-muted">No Orders Yet</h3>
                        <p class="text-secondary mb-4">You haven't placed any orders yet. Start your first order now!</p>
                        <a href="order.php" class="btn btn-cta rounded-pill px-4">
                            <i class="bi bi-cart-plus me-2"></i> Place Your First Order
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col"><i class="bi bi-hash me-1"></i> Order ID</th>
                                    <th scope="col"><i class="bi bi-calendar-event me-1"></i> Date</th>
                                    <th scope="col"><i class="bi bi-currency-peso me-1"></i> Total</th>
                                    <th scope="col"><i class="bi bi-info-circle me-1"></i> Status</th>
                                    <th scope="col" class="text-center"><i class="bi bi-gear me-1"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_history as $order): ?>
                                    <tr>
                                        <th scope="row" class="fw-bold">#<?php echo htmlspecialchars($order['id']); ?></th>
                                        <td><?php echo date("M d, Y, h:i A", strtotime(htmlspecialchars($order['order_date']))); ?></td>
                                        <td class="fw-bold text-success">₱<?php echo number_format(htmlspecialchars($order['total_amount']), 2); ?></td>
                                        <td>
                                            <?php
                                                $status = htmlspecialchars($order['status']);
                                                $badge_class = '';
                                                $icon = '';
                                                switch ($status) {
                                                    /* <<< MODIFIED: Added Picked Up Case, Removed Delivered, Changed Completed Style */
                                                    case 'Pending':
                                                        $badge_class = 'status-badge-pending';
                                                        $icon = 'bi-hourglass-split';
                                                        break;
                                                    case 'Confirmed':
                                                        $badge_class = 'status-badge-confirmed';
                                                        $icon = 'bi-check-circle';
                                                        break;
                                                    case 'Picked Up': // Added Case
                                                        $badge_class = 'status-badge-pickedup';
                                                        $icon = 'bi-box-arrow-up'; // Example icon
                                                        break;
                                                    case 'On the Way':
                                                        $badge_class = 'status-badge-ontheway';
                                                        $icon = 'bi-truck';
                                                        break;
                                                    /* case 'Delivered': Removed Case */
                                                    case 'Completed':
                                                        $badge_class = 'status-badge-completed'; // Use new style
                                                        $icon = 'bi-check2-all';
                                                        break;
                                                    case 'Cancelled':
                                                        $badge_class = 'status-badge-cancelled';
                                                        $icon = 'bi-x-circle';
                                                        break;
                                                    default:
                                                        $badge_class = 'bg-secondary';
                                                        $icon = 'bi-question-circle';
                                                }
                                            ?>
                                            <span class="badge rounded-pill <?php echo $badge_class; ?> px-3 py-2">
                                                <i class="bi <?php echo $icon; ?> me-1"></i><?php echo $status; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php /* <<< MODIFIED: Actions based on new flow */ ?>
                                            <?php if ($order['status'] == 'Confirmed'): ?>
                                                <form action="confirm_pickup.php" method="POST" class="d-inline"> <?php /* New target script */ ?>
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-info rounded-pill px-3" title="Click this once the driver has picked up your empty bottles">
                                                        <i class="bi bi-box-arrow-up me-1"></i> Confirm Pickup <?php /* New button text */ ?>
                                                    </button>
                                                </form>
                                            <?php elseif ($order['status'] == 'On the Way'): ?> <?php /* Changed condition */ ?>
                                                <form action="user_confirm_completion.php" method="POST" class="d-inline"> <?php /* Target completion script */ ?>
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3" title="Click this once the order is delivered and paid">
                                                        <i class="bi bi-check2-circle me-1"></i> Confirm Delivery & Payment <?php /* New button text */ ?>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                            <?php /* <<< End MODIFIED Actions */ ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="text-center mb-5">
                <a href="logout.php" class="btn btn-danger btn-lg rounded-pill shadow-sm px-5 py-3">
                    <i class="bi bi-box-arrow-right me-2"></i> Log Out
                </a>
            </div>
        </div>
    </div>
</div>

<footer class="bg-primary text-white py-4 mt-auto">
    <div class="container text-center">
        <p class="mb-0" style="font-size: 1rem;">&copy; 2024 Moya - Mineral Water Delivery. All rights reserved. | Rosario, La Union.</p>
    </div>
</footer>

<script>
function handleProfilePictureUpload(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const maxSize = 5 * 1024 * 1024; // 5MB

        // Validate file size
        if (file.size > maxSize) {
            alert('File size must be less than 5MB');
            return;
        }

        // Validate file type
        if (!file.type.startsWith('image/')) {
            alert('Please upload an image file');
            return;
        }

        // Preview the image
        const reader = new FileReader();
        reader.onload = function(e) {
            const profilePicContainer = document.querySelector('.profile-pic-container');
            profilePicContainer.innerHTML = `<img src="${e.target.result}" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
        };
        reader.readAsDataURL(file);

        // Here you would typically upload to server
        // For now, we'll just show a success message
        setTimeout(() => {
            alert('Profile picture updated successfully! (Note: This is a demo - implement server upload in production)');
        }, 500);
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>