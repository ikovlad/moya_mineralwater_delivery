<?php
// Include the database connection configuration
require_once "config.php";

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
    
    // Set default values for additional fields
    $address_city = "Rosario";
    $address_province = "La Union";
    $is_admin = 0; // Regular user by default

    // Basic validation
    if (empty($full_name) || empty($email) || empty($password) || empty($phone_number) || empty($address_barangay) || empty($address_detail)) {
        $error_message = "All fields are required. Please fill out the entire form.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif (!preg_match("/^09\d{9}$/", $phone_number)) {
        $error_message = "Phone number must be 11 digits starting with 09.";
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

        // Updated SQL to include all required columns
        $sql = "INSERT INTO users (full_name, email, password_hash, phone_number, address_barangay, address_detail, address_city, address_province, is_admin) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssssssi", 
                $param_name, 
                $param_email, 
                $param_hash, 
                $param_phone, 
                $param_barangay, 
                $param_address,
                $param_city,
                $param_province,
                $param_is_admin
            );

            // Set parameters
            $param_name = $full_name;
            $param_email = $email;
            $param_hash = $hashed_password;
            $param_phone = $phone_number;
            $param_barangay = $address_barangay;
            $param_address = $address_detail;
            $param_city = $address_city;
            $param_province = $address_province;
            $param_is_admin = $is_admin;

            if (mysqli_stmt_execute($stmt)) {
                // Store success message in session
                $_SESSION['signup_success'] = true;
                
                // Close statement and connection before redirect
                mysqli_stmt_close($stmt);
                mysqli_close($conn);
                
                // Redirect to index.html with login tab active
                header("Location: index.html#login-success");
                exit();
            } else {
                $error_message = "Database error: " . mysqli_error($conn);
            }

            mysqli_stmt_close($stmt);
        } else {
            $error_message = "Could not prepare statement: " . mysqli_error($conn);
        }
    }

    // 4. Handle Errors
    if (!empty($error_message)) {
        // Log the error for debugging
        error_log("Signup Error: " . $error_message);
        mysqli_close($conn);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Sign Up Error - Moya</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body {
                    background: linear-gradient(135deg, #e6f7f7 0%, #f0fbfb 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: 'Lato', sans-serif;
                }
                .error-card {
                    max-width: 500px;
                    padding: 2.5rem;
                    border-radius: 1rem;
                    box-shadow: 0 10px 30px rgba(0, 128, 128, 0.15);
                }
                .btn-primary {
                    background: linear-gradient(135deg, #008080 0%, #006666 100%);
                    border: none;
                    padding: 0.75rem 2rem;
                    font-weight: 600;
                    transition: all 0.3s ease;
                }
                .btn-primary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(0, 128, 128, 0.3);
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="error-card bg-white mx-auto text-center">
                    <div class="mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#dc3545" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4m.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/>
                        </svg>
                    </div>
                    <h3 class="text-danger mb-3 fw-bold">Sign Up Error</h3>
                    <div class="alert alert-danger text-start">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                    <a href="index.html" class="btn btn-primary mt-3 rounded-pill">
                        <i class="bi bi-arrow-left me-2"></i>Go Back to Home
                    </a>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}

// Close connection if still open
if (isset($conn)) {
    mysqli_close($conn);
}
?>