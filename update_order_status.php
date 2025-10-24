<?php
require_once "config.php";
session_start();

header('Content-Type: application/json');

// Security check: Ensure admin is logged in
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Ensure the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $status = isset($_POST['status']) ? $conn->real_escape_string($_POST['status']) : '';

    // Validate order_id
    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Order ID']);
        exit;
    }

    // <<< MODIFIED: Added 'Picked Up', Removed 'Delivered' >>>
    $allowed_statuses = ['Pending', 'Confirmed', 'Picked Up', 'On the Way', 'Completed', 'Cancelled'];

    // Check if the provided status is valid
    if (in_array($status, $allowed_statuses)) {
        // Prepare the SQL update statement
        $sql = "UPDATE orders SET status = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            // Bind parameters
            $stmt->bind_param("si", $status, $order_id);

            // Execute the statement
            if ($stmt->execute()) {
                // Check if any row was actually updated
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true]);
                } else {
                    // This might happen if the status was already set to the new value
                    // or if the order_id doesn't exist. Consider it a success in terms of the request processing.
                    echo json_encode(['success' => true, 'message' => 'No rows affected, status might already be set or ID not found.']);
                }
            } else {
                // Execution failed
                error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error); // Log error
                echo json_encode(['success' => false, 'message' => 'Database execute failed.']);
            }
            // Close the statement
            $stmt->close();
        } else {
            // Prepare failed
            error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error); // Log error
            echo json_encode(['success' => false, 'message' => 'Database prepare failed.']);
        }
    } else {
        // Invalid status provided
        echo json_encode(['success' => false, 'message' => 'Invalid status provided: ' . htmlspecialchars($status)]);
    }
    // Close the database connection
    $conn->close();
} else {
    // Invalid request method
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Only POST is accepted.']);
}
?>