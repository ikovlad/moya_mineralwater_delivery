<?php
require_once "config.php";
session_start();

// Security: Ensure an admin is logged in
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    // Store a message
    $_SESSION['alert_message'] = "You must be logged in to perform that action.";
    $_SESSION['alert_type'] = "danger";
    // Redirect to admin login
    header("location: admin_login.php"); 
    exit;
}

// Get the action from POST (for create/update) or GET (for delete)
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$user_type = $_POST['user_type'] ?? $_GET['user_type'] ?? null;

// Determine the table name based on user type
$table_name = ($user_type === 'admin') ? 'admins' : 'users';

try {
    // Use a switch to handle different actions
    switch ($action) {
        
        // --- CREATE ACTION ---
        case 'create':
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $password = trim($_POST['password']);
            
            // Always hash the password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            if ($user_type === 'user') {
                // Customer-specific fields
                $phone_number = trim($_POST['phone_number']);
                $address_barangay = trim($_POST['address_barangay']);
                // --- FIXED: Reads "address_detail" from the form ---
                $address_detail_from_form = trim($_POST['address_detail']);

                // --- FIXED: Inserts into "address_detail" column ---
                $sql = "INSERT INTO users (full_name, email, password_hash, phone_number, address_barangay, address_detail) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $full_name, $email, $password_hash, $phone_number, $address_barangay, $address_detail_from_form);
            
            } else {
                // Admin-specific fields
                $sql = "INSERT INTO admins (full_name, email, password_hash) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $full_name, $email, $password_hash);
            }
            
            if ($stmt->execute()) {
                $_SESSION['alert_message'] = ucfirst($user_type) . " created successfully.";
                $_SESSION['alert_type'] = "success";
            } else {
                // Check for duplicate email
                if ($conn->errno == 1062) {
                    $_SESSION['alert_message'] = "Error: An account with this email address already exists.";
                } else {
                    $_SESSION['alert_message'] = "Error creating " . $user_type . ": " . $stmt->error;
                }
                $_SESSION['alert_type'] = "danger";
            }
            $stmt->close();
            break;

        // --- UPDATE ACTION ---
        case 'update':
            $user_id = $_POST['user_id'];
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $password = trim($_POST['password']); // Get the new password field

            // Base SQL and parameters
            $params = [];
            $param_types = "";

            if ($user_type === 'user') {
                // Customer-specific fields
                $phone_number = trim($_POST['phone_number']);
                $address_barangay = trim($_POST['address_barangay']);
                // --- FIXED: Reads "address_detail" from the form ---
                $address_detail_from_form = trim($_POST['address_detail']);
                
                // Start building the query
                $sql_parts = [
                    "full_name = ?",
                    "email = ?",
                    "phone_number = ?",
                    "address_barangay = ?",
                    // --- FIXED: Updates "address_detail" column ---
                    "address_detail = ?" 
                ];
                $params = [$full_name, $email, $phone_number, $address_barangay, $address_detail_from_form];
                $param_types = "sssss";

            } else {
                // Admin-specific fields
                $sql_parts = ["full_name = ?", "email = ?"];
                $params = [$full_name, $email];
                $param_types = "ss";
            }

            // --- Check if password needs to be updated ---
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql_parts[] = "password_hash = ?"; // Add password to the query
                $params[] = $password_hash;
                $param_types .= "s";
            }

            // Add the user ID to the end for the WHERE clause
            $params[] = $user_id;
            $param_types .= "i";

            // Finalize the SQL
            $sql = "UPDATE $table_name SET " . implode(", ", $sql_parts) . " WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($param_types, ...$params);

            if ($stmt->execute()) {
                $_SESSION['alert_message'] = ucfirst($user_type) . " updated successfully.";
                $_SESSION['alert_type'] = "success";
            } else {
                 if ($conn->errno == 1062) {
                    $_SESSION['alert_message'] = "Error: An account with this email address already exists.";
                } else {
                    $_SESSION['alert_message'] = "Error updating " . $user_type . ": " . $stmt->error;
                }
                $_SESSION['alert_type'] = "danger";
            }
            $stmt->close();
            break;
            
        // --- DELETE ACTION (from GET link) ---
        case 'delete':
            $user_id = $_GET['id'];

            // CRITICAL: Prevent admin from deleting themselves
            if ($user_type === 'admin' && isset($_SESSION['admin_id']) && $user_id == $_SESSION['admin_id']) {
                $_SESSION['alert_message'] = "Error: You cannot delete your own account.";
                $_SESSION['alert_type'] = "danger";
            } else {
                $sql = "DELETE FROM $table_name WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['alert_message'] = ucfirst($user_type) . " deleted successfully.";
                    $_SESSION['alert_type'] = "success";
                } else {
                    $_SESSION['alert_message'] = "Error deleting " . $user_type . ": " . $stmt->error;
                    $_SESSION['alert_type'] = "danger";
                }
                $stmt->close();
            }
            break;
        
        default:
            $_SESSION['alert_message'] = "Invalid action.";
            $_SESSION['alert_type'] = "warning";
            break;
    }
} catch (Exception $e) {
    // Catch any general errors
    $_SESSION['alert_message'] = "An unexpected error occurred: " . $e->getMessage();
    $_SESSION['alert_type'] = "danger";
    // Log this error for debugging
    error_log("admin_manage_user.php error: " . $e->getMessage());
}

$conn->close();

// Always redirect back to the user management page
header("location: admin_users.php");
exit;
?>