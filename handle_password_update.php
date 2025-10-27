<?php
// handle_password_update.php
session_start();
require_once "config.php"; // Ensure timezone is set here

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: forgot_password.php");
    exit;
}

$email = trim($_POST['email']);
$token = trim($_POST['token']);
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

function redirectWithError($message, $token, $email) {
    $_SESSION['reset_update_message'] = $message;
    $_SESSION['reset_update_message_type'] = "danger";
    $queryString = "?token=" . urlencode($token);
    if (!empty($email)) { $queryString .= "&email=" . urlencode($email); }
    header("location: reset_password.php" . $queryString);
    exit;
}

// Basic Validations
if (empty($token) || empty($new_password) || empty($confirm_password)) {
    redirectWithError("Token and password fields are required.", $token, $email);
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
     redirectWithError("Invalid email associated with this request.", $token, $email);
}
if ($new_password !== $confirm_password) {
    redirectWithError("Passwords do not match.", $token, $email);
}
if (strlen($new_password) < 8 || !preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
     redirectWithError("Password must be at least 8 characters and include a number.", $token, $email);
}

// --- Validate Token and Email ---
$sql_find = "SELECT reset_token_hash, reset_token_expires_at FROM users WHERE email = ?";
if ($stmt_find = $conn->prepare($sql_find)) {
    $stmt_find->bind_param("s", $email);
    if (!$stmt_find->execute()) {
        error_log("Execute failed (find user): " . $stmt_find->error);
        redirectWithError("Database error (E1).", $token, $email);
    }
    $result = $stmt_find->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $stored_hash = $user['reset_token_hash'];
        $expiry_time_str = $user['reset_token_expires_at'];
        $expiry_time = null;

        try {
            if ($expiry_time_str) {
                $expiry_time = new DateTime($expiry_time_str, new DateTimeZone(date_default_timezone_get())); // Use configured timezone
            }
            $current_time = new DateTime('now', new DateTimeZone(date_default_timezone_get()));

            if ($stored_hash === null || $expiry_time === null) {
                redirectWithError("Invalid or expired reset request.", $token, $email);
            } elseif ($expiry_time < $current_time) {
                redirectWithError("Password reset token has expired.", $token, $email);
            }
            elseif (password_verify($token, $stored_hash)) {
                // --- Token is valid ---
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $sql_update = "UPDATE users SET password_hash = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE email = ?";
                if ($stmt_update = $conn->prepare($sql_update)) {
                    $stmt_update->bind_param("ss", $new_password_hash, $email);
                    if ($stmt_update->execute()) {
                        // --- SUCCESS ---
                        $_SESSION['login_message'] = "Password updated! Please log in."; // Message for login page
                        $_SESSION['login_message_type'] = "success";
                        $stmt_update->close(); $stmt_find->close(); $conn->close();
                        header("location: index.html"); // Redirect to login
                        exit;
                    } else { /* Handle update execute error */ }
                    $stmt_update->close();
                } else { /* Handle update prepare error */ }
            } else {
                redirectWithError("Invalid password reset token.", $token, $email);
            }

        } catch (Exception $e) { /* Handle DateTime Error */ }

    } else {
        redirectWithError("Invalid request details.", $token, $email);
    }
    $stmt_find->close();
} else { /* Handle find prepare error */ }

$conn->close();
// Fallback redirect
redirectWithError("An unexpected error occurred (E6).", $token, $email);
?>