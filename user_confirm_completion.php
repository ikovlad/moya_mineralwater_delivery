<?php
// user_confirm_completion.php
session_start();
require_once "config.php";

// User must be logged in.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.html");
    exit;
}

// An order_id must be provided via POST.
if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
    header("location: profile.php");
    exit;
}

$order_id = $_POST['order_id'];
$user_id = $_SESSION['id'];

// <<< MODIFIED: Changed the required status from 'Delivered' to 'On the Way' >>>
// SECURITY: This query updates the status to 'Completed' ONLY IF
// the order belongs to the logged-in user AND its current status is 'On the Way'.
$sql = "UPDATE orders SET status = 'Completed' WHERE id = ? AND user_id = ? AND status = 'On the Way'"; // <<< Change is HERE

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $order_id, $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['profile_message'] = "Order #" . $order_id . " confirmed and completed. Thank you for your business!";
            $_SESSION['message_type'] = "success";
        } else {
             // Updated warning message for clarity
            $_SESSION['profile_message'] = "Order #" . $order_id . " could not be updated. Status might not be 'On the Way'.";
            $_SESSION['message_type'] = "warning";
        }
    } else {
        $_SESSION['profile_message'] = "A database error occurred. Please try again.";
        $_SESSION['message_type'] = "danger";
    }

    $stmt->close();
} else {
     // Added more specific error for prepare failure
    $_SESSION['profile_message'] = "Database error preparing statement. Please try again.";
    $_SESSION['message_type'] = "danger";
}

$conn->close();

// Always redirect back to the profile page.
header("location: profile.php");
exit;
?>