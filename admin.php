<?php
// Admin
session_start(); // Session is started only ONCE at the top.
require_once "config.php";

$email = $password = "";
$login_err = ""; 

if (!function_exists('sanitize_input')) {
    function sanitize_input($conn, $data) {
        return htmlspecialchars(mysqli_real_escape_string($conn, trim($data)));
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = sanitize_input($conn, $_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';

    if (empty($email) || empty($password)) {
        $login_err = "Email and password are required.";
    } else {
        $sql = "SELECT id, full_name, password_hash FROM admins WHERE email = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {                    
                    mysqli_stmt_bind_result($stmt, $id, $full_name, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        
                        if (password_verify($password, $hashed_password)) {
                            // SUCCESS: Password is correct.

                            // CHANGE 1: Use a unique session variable for admins.
                            $_SESSION["admin_loggedin"] = true; 
                            
                            // Use distinct session keys for admin details to avoid conflicts with user sessions.
                            $_SESSION["admin_id"] = $id;
                            $_SESSION["admin_full_name"] = $full_name;
                            
                            // CHANGE 2: Redirect to the correct admin dashboard.
                            header("location: admin_dashboard.php");
                            exit;
                        } else {
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else {
                    $login_err = "Invalid email or password.";
                }
            } else {
                $login_err = "Database error. Please try again.";
            }
            mysqli_stmt_close($stmt);
        } else {
             $login_err = "Database connection error.";
        }
    }
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moya Admin - Secure Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --moya-primary: #008080;
            --moya-light: #f8f9fa;
            --moya-dark-text: #34495e;
            --border-color: #dee2e6;
            --card-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--moya-light);
            color: var(--moya-dark-text);
            display: flex;
            flex-direction: column; 
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1rem;
        }
        
        .login-container {
            width: 100%;
            max-width: 480px;
        }
        
        .login-card {
            background-color: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 3rem 2.5rem;
            box-shadow: var(--card-shadow);
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--moya-primary), #006666);
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 4px 12px rgba(0, 128, 128, 0.2);
        }
        
        .logo-icon i {
            font-size: 1.75rem;
            color: white;
        }
        
        .login-card-header h1 { 
            color: var(--moya-dark-text);
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }
        
        .login-card-header p { 
            color: #6c757d;
            font-size: 0.95rem;
            font-weight: 400;
            margin-bottom: 0;
        }
        
        .form-label {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--moya-dark-text);
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
        }
        
        .form-control:focus { 
            border-color: var(--moya-primary);
            box-shadow: 0 0 0 3px rgba(0, 128, 128, 0.1);
            outline: none;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group-text { 
            background-color: transparent;
            border: 1px solid var(--border-color);
            border-right: none;
            border-radius: 0.5rem 0 0 0.5rem;
            color: #8895a7;
            padding: 0.75rem 1rem;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 0.5rem 0.5rem 0;
            padding-left: 0.5rem;
        }
        
        .input-group:focus-within .input-group-text {
            border-color: var(--moya-primary);
            color: var(--moya-primary);
        }
        
        .btn-primary { 
            background-color: var(--moya-primary);
            border: none;
            padding: 0.875rem;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            letter-spacing: 0.3px;
        }
        
        .btn-primary:hover { 
            background-color: #006666;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 128, 128, 0.3);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .forgot-password { 
            text-align: right;
            margin-top: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .forgot-password a { 
            color: var(--moya-primary);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        
        .forgot-password a:hover { 
            color: #006666;
            text-decoration: underline;
        }
        
        .forgot-password i {
            font-size: 0.75rem;
            margin-right: 0.25rem;
        }
        
        .alert {
            border-radius: 0.5rem;
            border: none;
            font-size: 0.9rem;
            padding: 0.875rem 1rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background-color: #fff5f5;
            color: #c53030;
            border-left: 3px solid #c53030;
        }
        
        .alert-success {
            background-color: #f0fdf4;
            color: #15803d;
            border-left: 3px solid #15803d;
        }
        
        .alert-info {
            background-color: #eff6ff;
            color: #1e40af;
            border-left: 3px solid #1e40af;
        }
        
        /* Loading state for button */
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Responsive adjustments */
        @media (max-width: 576px) {
            .login-card {
                padding: 2rem 1.5rem;
            }
            
            .login-card-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
            </div>
            
            <div class="login-card-header">
                <h1>Admin Login</h1>
                <p>Sign in to access the dashboard</p> <br>
            </div>

            <?php
            // Display login errors
            if (!empty($login_err)) {
                echo '<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>' . htmlspecialchars($login_err) . '</div>';
            }
            
            // Display success messages (e.g., after password reset)
            if (isset($_SESSION['admin_login_message'])) {
                $msg_type = $_SESSION['admin_login_message_type'] ?? 'info';
                $icon = $msg_type === 'success' ? 'check-circle' : 'info-circle';
                echo '<div class="alert alert-' . htmlspecialchars($msg_type) . '"><i class="bi bi-' . $icon . ' me-2"></i>' . htmlspecialchars($_SESSION['admin_login_message']) . '</div>';
                unset($_SESSION['admin_login_message']);
                unset($_SESSION['admin_login_message_type']);
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" novalidate>
                
                <div class="mb-4">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="admin@moya.com" required>
                    </div>
                </div>

                <div class="mb-2">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <div class="forgot-password">
                    <a href="admin_forgot_password.php">
                        <i class="bi bi-question-circle-fill"></i> Forgot Password?
                    </a>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </div>
                
            </form>
        </div>
    </div>

</body>
</html>