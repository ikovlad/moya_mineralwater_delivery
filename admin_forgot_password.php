<?php
// admin_forgot_password.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Password Reset - Moya</title>
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
        .reset-card {
            max-width: 500px;
            width: 100%;
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            padding: 2.5rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }
        .reset-card-header { text-align: center; margin-bottom: 2rem; }
        .reset-card-header h1 { color: var(--primary-color); font-weight: 700; font-size: 1.75rem; }
        .reset-card-header p { color: #6c757d; }
        .btn-primary { background-color: var(--primary-color); border: none; padding: 0.75rem; font-weight: 600; transition: background-color 0.3s ease; }
        .btn-primary:hover { background-color: #006666; }
        .form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 0.25rem rgba(0, 128, 128, 0.25); }
        .input-group-text { background-color: #f8f9fa; }
        .back-link { text-align: center; margin-top: 1.5rem; }
        .back-link a { color: var(--primary-color); text-decoration: none; font-weight: 500; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="reset-card">
        <div class="reset-card-header">
            <h1><i class="bi bi-shield-lock"></i> Admin Password Reset</h1>
            <p>Enter your admin email to receive reset instructions</p>
        </div>

        <?php
        if (isset($_SESSION['admin_reset_message'])) {
            $msg_type = $_SESSION['admin_reset_message_type'] ?? 'info';
            echo '<div class="alert alert-' . htmlspecialchars($msg_type) . '">' . htmlspecialchars($_SESSION['admin_reset_message']) . '</div>';
            unset($_SESSION['admin_reset_message']);
            unset($_SESSION['admin_reset_message_type']);
        }
        ?>

        <form action="handle_admin_reset_request.php" method="POST" class="needs-validation" novalidate>
            
            <div class="mb-4">
                <label for="email" class="form-label fw-semibold">Admin Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                    <input type="email" class="form-control" id="email" name="email" placeholder="admin@moya.com" required>
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>
                <small class="text-muted">We'll send a reset link to this email if it's registered in our system.</small>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-send-fill me-2"></i>Send Reset Link
                </button>
            </div>
            
        </form>

        <div class="back-link">
            <a href="admin.php"><i class="bi bi-arrow-left"></i> Back to Admin Login</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>