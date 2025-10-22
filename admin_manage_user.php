<?php
// admin_manage_user.php
session_start();
require_once "config.php";

// 1. --- SECURITY CHECK ---
// Ensure an admin is logged in
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    $_SESSION['alert_message'] = "Error: You are not authorized.";
    $_SESSION['alert_type'] = "danger";
    header("location: admin_users.php");
    exit;
}

// 2. --- HANDLE "EDIT" REQUESTS (from the modal form) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $user_type = $_POST['user_type'];
    $user_id = $_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    
    if ($user_type == 'user') {
        // --- Edit a Customer ---
        $phone = trim($_POST['phone_number']);
        $sql = "UPDATE users SET full_name = ?, email = ?, phone_number = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
            if ($stmt->execute()) {
                $_SESSION['alert_message'] = "Customer #" . $user_id . " updated successfully.";
                $_SESSION['alert_type'] = "success";
            } else {
                $_SESSION['alert_message'] = "Error updating customer.";
                $_SESSION['alert_type'] = "danger";
            }
            $stmt->close();
        }

    } elseif ($user_type == 'admin') {
        // --- Edit an Admin ---
        $sql = "UPDATE admins SET full_name = ?, email = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssi", $full_name, $email, $user_id);
            if ($stmt->execute()) {
                $_SESSION['alert_message'] = "Admin #" . $user_id . " updated successfully.";
                $_SESSION['alert_type'] = "success";
            } else {
                $_SESSION['alert_message'] = "Error updating admin.";
                $_SESSION['alert_type'] = "danger";
            }
            $stmt->close();
        }
    }
}

// 3. --- HANDLE "DELETE" REQUESTS (from the modal link) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    
    $user_type = $_GET['user_type'];
    $user_id = $_GET['id'];
    
    if ($user_type == 'user') {
        // --- Delete a Customer ---
        $sql = "DELETE FROM users WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $_SESSION['alert_message'] = "Customer #" . $user_id . " has been deleted.";
                $_SESSION['alert_type'] = "success";
            } else {
                $_SESSION['alert_message'] = "Error deleting customer.";
                $_SESSION['alert_type'] = "danger";
            }
            $stmt->close();
        }
        
    } elseif ($user_type == 'admin') {
        // --- Delete an Admin ---
        // ** CRITICAL SAFETY CHECK **
        if ($user_id == $_SESSION['admin_id']) {
            $_SESSION['alert_message'] = "Error: You cannot delete your own account!";
            $_SESSION['alert_type'] = "danger";
        } else {
            $sql = "DELETE FROM admins WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $_SESSION['alert_message'] = "Admin #" . $user_id . " has been deleted.";
                    $_SESSION['alert_type'] = "success";
                } else {
                    $_SESSION['alert_message'] = "Error deleting admin.";
                    $_SESSION['alert_type'] = "danger";
                }
                $stmt->close();
            }
        }
    }
}

// 4. --- REDIRECT BACK ---
// After performing the action, always go back to the user list.
header("location: admin_users.php");
exit;
?>