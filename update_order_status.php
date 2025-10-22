<?php
require_once "config.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = intval($_POST['order_id']);
    $status = $conn->real_escape_string($_POST['status']);
    
    $allowed_statuses = ['Pending', 'Confirmed', 'On the Way', 'Delivered', 'Completed', 'Cancelled'];

    if (in_array($status, $allowed_statuses)) {
        $sql = "UPDATE orders SET status = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $status, $order_id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Execute failed']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Prepare failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
    }
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>