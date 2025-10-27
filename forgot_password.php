<?php
// forgot_password.php
session_start();
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: home.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Moya</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --moya-primary: #008080; --moya-bg: #f5fcfc; }
        body { font-family: 'Inter', sans-serif; background-color: var(--moya-bg); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1rem;}
        .card { max-width: 500px; width: 100%; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .btn-primary { background-color: var(--moya-primary); border-color: var(--moya-primary); }
        .btn-primary:hover { background-color: #006666; border-color: #006666; }
    </style>
</head>
<body>
    <div class="card p-4 p-md-5 rounded-4">
        <h2 class="text-center fw-bold mb-4" style="color: var(--moya-primary);">Forgot Your Password?</h2>
        <p class="text-center text-muted mb-4">Enter your email. If an account exists, we'll send password reset instructions.</p>

        <?php
        // Display messages
        if (isset($_SESSION['reset_message'])) {
            $msg_type = $_SESSION['reset_message_type'] ?? 'info';
            echo '<div class="alert alert-' . htmlspecialchars($msg_type) . ' small">' . htmlspecialchars($_SESSION['reset_message']) . '</div>'; // Use htmlspecialchars for security
            unset($_SESSION['reset_message']);
            unset($_SESSION['reset_message_type']);
        }
        ?>

        <form action="handle_reset_request.php" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email Address</label>
                <input type="email" class="form-control rounded-3 p-3" id="email" name="email" required placeholder="Enter your registered email">
            </div>
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill">Request Password Reset</button>
            </div>
             <div class="text-center mt-3">
                 <a href="index.html" class="text-decoration-none">Back to Login</a>
             </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>