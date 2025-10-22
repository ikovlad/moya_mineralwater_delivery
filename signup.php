<?php
// Include the database connection configuration
require_once "config.php";

session_start();

$error_message = "";
$success_message = "";

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Sanitize and Validate Inputs
    $full_name = sanitize_input($conn, $_POST['full_name'] ?? '');
    $email = sanitize_input($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone_number = sanitize_input($conn, $_POST['phone_number'] ?? '');
    $address_barangay = sanitize_input($conn, $_POST['address_barangay'] ?? '');
    $address_detail = sanitize_input($conn, $_POST['address_detail'] ?? '');

    // Basic validation
    if (empty($full_name) || empty($email) || empty($password) || empty($phone_number) || empty($address_barangay) || empty($address_detail)) {
        $error_message = "All fields are required. Please fill out the entire form.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    }

    if (empty($error_message)) {
        // 2. Check if Email Already Exists
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $error_message = "This email address is already registered.";
                }
            } else {
                $error_message = "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }


    // 3. Insert New User Data
    if (empty($error_message)) {
        // Hash the password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (full_name, email, password_hash, phone_number, address_barangay, address_detail) VALUES (?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssss", $param_name, $param_email, $param_hash, $param_phone, $param_barangay, $param_address);

            // Set parameters
            $param_name = $full_name;
            $param_email = $email;
            $param_hash = $hashed_password;
            $param_phone = $phone_number;
            $param_barangay = $address_barangay;
            $param_address = $address_detail;

            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Account created successfully! You can now log in.";
                // Redirect user to the login page (or show a success page)
                header("location: index.html?status=signup_success");
                exit();
            } else {
                $error_message = "Database error: Could not complete registration.";
            }

            mysqli_stmt_close($stmt);
        }
    }

    // 4. Handle Errors (If execution reaches here, there was an error)
    if (!empty($error_message)) {
        // Simple error output for development. In production, this should redirect with a status flag.
        echo "<h2>Sign Up Error</h2>";
        echo "<p style='color:red;'>$error_message</p>";
        echo "<p><a href='index.php'>Go back to Home</a></p>";
    }
}

// Close connection
mysqli_close($conn);

?>
