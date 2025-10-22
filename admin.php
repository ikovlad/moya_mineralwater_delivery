<?php
// --- CORRECTED VERSION ---
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #008080;
            --background-start: #e0f2f1;
            --background-end: #b2dfdb;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--background-start), var(--background-end));
            display: flex;
            flex-direction: column; 
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
        }
        .login-card {
            max-width: 450px;
            width: 100%;
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            padding: 2.5rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }
        .login-card-header { text-align: center; margin-bottom: 2rem; }
        .login-card-header h1 { color: var(--primary-color); font-weight: 700; }
        .login-card-header p { color: #6c757d; }
        .btn-primary { background-color: var(--primary-color); border: none; padding: 0.75rem; font-weight: 600; transition: background-color 0.3s ease; }
        .btn-primary:hover { background-color: #006666; }
        .form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 0.25rem rgba(0, 128, 128, 0.25); }
        .input-group-text { background-color: #f8f9fa; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-card-header">
            <h1>Moya Admin Panel</h1>
            <p>Please sign in to continue</p>
        </div>

        <?php
        if (!empty($login_err)) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($login_err) . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" novalidate>
            
            <div class="mb-4">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="e.g., admin@moya.com" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Sign In</button>
            </div>
            
        </form>
    </div>

</body>
</html>