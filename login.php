<?php
session_start();
require_once "config.php";

$email = $password = "";
$login_err = ""; // Variable to hold login error messages

if (!function_exists('sanitize_input')) {
    function sanitize_input($conn, $data) {
        return htmlspecialchars(mysqli_real_escape_string($conn, trim($data)));
    }
}

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Sanitize inputs
    $email = sanitize_input($conn, $_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';

    // Basic validation
    if (empty($email)) {
        $login_err = "Please enter your email.";
    } elseif (empty($password)) {
        $login_err = "Please enter your password.";
    }

    if (empty($login_err)) {
        // **MODIFIED SQL QUERY**
        // This query now ONLY finds users who are NOT admins.
        $sql = "SELECT id, full_name, password_hash, address_barangay FROM users WHERE email = ? AND is_admin = 0";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                // Check if a regular user account with that email exists
                if (mysqli_stmt_num_rows($stmt) == 1) {                    
                    mysqli_stmt_bind_result($stmt, $id, $full_name, $hashed_password, $address_barangay);
                    if (mysqli_stmt_fetch($stmt)) {
                        
                        // Verify password
                        if (password_verify($password, $hashed_password)) {
                            // SUCCESS: Credentials are correct for a regular user.
                            // Start a new session.
                            session_start();
                            
                            // Store user data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["full_name"] = $full_name;
                            $_SESSION["address_barangay"] = $address_barangay;
                            $_SESSION["is_admin"] = 0; // Set as non-admin
                            
                            // Redirect to the user home page
                            header("location: home.php");
                            exit; 

                        } else {
                            // Password is not valid
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else {
                    // Email doesn't exist or the user is an admin
                    $login_err = "Invalid email or password.";
                }
            } else {
                $login_err = "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);
}

// This part is for displaying the error on your login page.
// Place this PHP snippet inside your HTML form.
if (!empty($login_err)) {
    echo '<div class="alert alert-danger" role="alert" style="margin-top: 1rem;">' . htmlspecialchars($login_err) . '</div>';
}
?>