<?php
// confirm_pickup.php
session_start();
require_once "config.php";

// 1. --- SECURITY AND VALIDATION ---

// User must be logged in.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.html");
    exit;
}

// An order_id must be provided via POST.
if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
    // Redirect to profile if no order ID is specified.
    header("location: profile.php");
    exit;
}

// 2. --- PROCESS THE PICKUP CONFIRMATION ---

$order_id = $_POST['order_id'];
$user_id = $_SESSION['id']; // Get the ID of the currently logged-in user.

// ** CRITICAL SECURITY STEP **
// This query updates the order status to 'Picked Up' ONLY IF:
// 1. The order ID matches.
// 2. The order BELONGS to the currently logged-in user.
// 3. The current status is 'Confirmed'.
$sql = "UPDATE orders SET status = 'Picked Up' WHERE id = ? AND user_id = ? AND status = 'Confirmed'";

if ($stmt = $conn->prepare($sql)) {
    // Bind variables
    $stmt->bind_param("ii", $order_id, $user_id);

    // Attempt to execute
    if ($stmt->execute()) {
        // Check if a row was actually changed.
        if ($stmt->affected_rows > 0) {
            // Success: Set success message.
            $_SESSION['profile_message'] = "Thank you! Pickup confirmed for Order #" . $order_id . ". Waiting for delivery.";
            $_SESSION['message_type'] = "success";
        } else {
            // No rows changed. Could be wrong status or wrong user.
            $_SESSION['profile_message'] = "Order #" . $order_id . " pickup could not be confirmed. Status may not be 'Confirmed'.";
            $_SESSION['message_type'] = "warning";
        }
    } else {
        // Query failed.
        $_SESSION['profile_message'] = "A database error occurred. Please try again.";
        $_SESSION['message_type'] = "danger";
    }

    // Close statement
    $stmt->close();
} else {
     // Prepare failed
    $_SESSION['profile_message'] = "Database prepare error. Please try again.";
    $_SESSION['message_type'] = "danger";
}

// Close connection
$conn->close();

// 3. --- REDIRECT BACK ---
// Always redirect back to the profile page.
header("location: profile.php");
exit;
?>