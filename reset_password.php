<?php
// reset_password.php
session_start();

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? ''; // Email is needed to find the user
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Moya</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
         :root { --moya-primary: #008080; --moya-bg: #f5fcfc; }
        body { font-family: 'Inter', sans-serif; background-color: var(--moya-bg); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1rem; }
        .card { max-width: 500px; width: 100%; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .btn-primary { background-color: var(--moya-primary); border-color: var(--moya-primary); }
        .btn-primary:hover { background-color: #006666; border-color: #006666; }
    </style>
</head>
<body>
    <div class="card p-4 p-md-5 rounded-4">
        <h2 class="text-center fw-bold mb-4" style="color: var(--moya-primary);">Set New Password</h2>
        <p class="text-center text-muted mb-4">Enter the reset token (from email link) and choose a new password.</p>

         <?php
        if (isset($_SESSION['reset_update_message'])) {
            $msg_type = $_SESSION['reset_update_message_type'] ?? 'info';
            echo '<div class="alert alert-' . htmlspecialchars($msg_type) . '">' . htmlspecialchars($_SESSION['reset_update_message']) . '</div>';
            unset($_SESSION['reset_update_message']);
            unset($_SESSION['reset_update_message_type']);
        }
        ?>

        <form action="handle_password_update.php" method="POST" class="needs-validation" novalidate>
            <!-- Hidden field for email is crucial -->
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

            <div class="mb-3">
                <label for="token" class="form-label fw-semibold">Reset Token</label>
                <!-- Token might be pre-filled from URL, still make it required -->
                <input type="text" class="form-control rounded-3 p-3" id="token" name="token" value="<?php echo htmlspecialchars($token); ?>" required placeholder="Token from email link">
                 <div class="invalid-feedback">Reset token is required.</div>
            </div>

            <div class="mb-3">
                <label for="new_password" class="form-label fw-semibold">New Password</label>
                <input type="password" class="form-control rounded-3 p-3" id="new_password" name="new_password" required minlength="8" pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$" title="Must be 8+ characters with at least one number.">
                <div class="invalid-feedback">Password must be 8+ characters with at least one number.</div>
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="form-label fw-semibold">Confirm New Password</label>
                <input type="password" class="form-control rounded-3 p-3" id="confirm_password" name="confirm_password" required>
                <div class="invalid-feedback">Please confirm your new password.</div>
            </div>

            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill">Reset Password</button>
            </div>
             <div class="text-center mt-3">
                  <!-- Link back to login -->
                 <a href="index.html" class="text-decoration-none">Back to Login</a>
             </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple client-side validation for password match & form feedback
        (function () { /* Keep existing validation script */ })();
    </script>
</body>
</html>