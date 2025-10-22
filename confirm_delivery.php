<?php
session_start();
require_once "config.php";

// 1. --- SECURITY AND VALIDATION ---

// User must be logged in to confirm an order.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Redirect to login if not logged in.
    header("location: index.html");
    exit;
}

// This script only works if an order_id is provided.
if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
    // Redirect to profile if no order ID is specified.
    header("location: profile.php");
    exit;
}

// 2. --- PROCESS THE CONFIRMATION ---

$order_id = $_POST['order_id'];
$user_id = $_SESSION['id']; // Get the ID of the currently logged-in user.

// ** CRITICAL SECURITY STEP **
// This query updates the order status ONLY IF:
// 1. The order ID matches.
// 2. The order BELONGS to the currently logged-in user.
// 3. The current status is 'On the Way'.
// This prevents a user from confirming another user's order or a pending/completed order.
$sql = "UPDATE orders SET status = 'Delivered' WHERE id = ? AND user_id = ? AND status = 'On the Way'";

if ($stmt = $conn->prepare($sql)) {
    // Bind variables to the prepared statement as parameters
    $stmt->bind_param("ii", $order_id, $user_id);
    
    // Attempt to execute the prepared statement
    if ($stmt->execute()) {
        // Check if a row was actually changed.
        if ($stmt->affected_rows > 0) {
            // Success: The order was updated. Set a success message.
            $_SESSION['profile_message'] = "Thank you! Order #" . $order_id . " has been marked as delivered.";
            $_SESSION['message_type'] = "success";
        } else {
            // No rows changed. This could be because the order didn't belong to the user
            // or its status wasn't 'On the Way'. Set an info message.
            $_SESSION['profile_message'] = "Order #" . $order_id . " could not be updated. It may have already been confirmed or is not yet out for delivery.";
            $_SESSION['message_type'] = "warning";
        }
    } else {
        // The query failed to execute.
        $_SESSION['profile_message'] = "A database error occurred. Please try again.";
        $_SESSION['message_type'] = "danger";
    }
    
    // Close statement
    $stmt->close();
}

// Close connection
$conn->close();

// 3. --- REDIRECT BACK ---
// After processing, always redirect the user back to their profile page to see the result.
header("location: profile.php");
exit;
?>
