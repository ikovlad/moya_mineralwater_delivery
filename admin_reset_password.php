<?php
// admin_reset_password.php
session_start();

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Admin Password - Moya</title>
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
        
        .reset-container {
            width: 100%;
            max-width: 540px;
        }
        
        .reset-card {
            background-color: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 3rem 2.5rem;
            box-shadow: var(--card-shadow);
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
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
        
        .reset-card-header h1 { 
            color: var(--moya-dark-text);
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
            text-align: center;
        }
        
        .reset-card-header p { 
            color: #6c757d;
            font-size: 0.95rem;
            text-align: center;
            margin-bottom: 0;
        }
        
        .security-badge {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 0.5rem;
            padding: 0.875rem 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .security-badge i {
            color: #ffc107;
            font-size: 1.1rem;
        }
        
        .security-badge strong {
            color: var(--moya-dark-text);
            display: block;
            margin-top: 0.25rem;
            font-size: 0.95rem;
        }
        
        .security-badge p {
            margin: 0.5rem 0 0 0;
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .form-label {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--moya-dark-text);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        
        .form-control:focus { 
            border-color: var(--moya-primary);
            box-shadow: 0 0 0 3px rgba(0, 128, 128, 0.1);
        }
        
        .input-group-text { 
            background-color: transparent;
            border: 1px solid var(--border-color);
            border-right: none;
            border-radius: 0.5rem 0 0 0.5rem;
            color: #8895a7;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 0.5rem 0.5rem 0;
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
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover { 
            background-color: #006666;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 128, 128, 0.3);
        }
        
        .back-link { 
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a { 
            color: var(--moya-primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .back-link a:hover { 
            color: #006666;
            text-decoration: underline;
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
        
        .help-text {
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>

    <div class="reset-container">
        <div class="reset-card">
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
            </div>
            
            <div class="reset-card-header">
                <h1>Set New Password</h1>
                <p>Enter your reset token and choose a new secure password</p>
            </div>

            <div class="security-badge">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>Admin Account Security</strong>
                <p class="mb-0">Use a strong password with at least 8 characters including numbers.</p>
            </div>

             <?php
            if (isset($_SESSION['admin_reset_update_message'])) {
                $msg_type = $_SESSION['admin_reset_update_message_type'] ?? 'info';
                $icon = $msg_type === 'success' ? 'check-circle' : 'exclamation-circle';
                echo '<div class="alert alert-' . htmlspecialchars($msg_type) . '"><i class="bi bi-' . $icon . ' me-2"></i>' . htmlspecialchars($_SESSION['admin_reset_update_message']) . '</div>';
                unset($_SESSION['admin_reset_update_message']);
                unset($_SESSION['admin_reset_update_message_type']);
            }
            ?>

            <form action="handle_admin_password_update.php" method="POST" class="needs-validation" novalidate>
                <!-- Hidden field for email -->
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

                <div class="mb-3">
                    <label for="token" class="form-label">
                        <i class="bi bi-key-fill me-1"></i> Reset Token
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                        <input type="text" class="form-control" id="token" name="token" value="<?php echo htmlspecialchars($token); ?>" required placeholder="Token from email">
                    </div>
                    <div class="invalid-feedback">Reset token is required.</div>
                    <small class="help-text">Copy this from the email link we sent you</small>
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label">
                        <i class="bi bi-lock-fill me-1"></i> New Password
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-asterisk"></i></span>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8" pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$" title="Must be 8+ characters with at least one number.">
                    </div>
                    <div class="invalid-feedback">Password must be 8+ characters with at least one number.</div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label">
                        <i class="bi bi-lock-fill me-1"></i> Confirm New Password
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-asterisk"></i></span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="invalid-feedback">Please confirm your new password.</div>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle me-2"></i>Reset Admin Password
                    </button>
                </div>
            </form>

            <div class="back-link">
                <a href="admin.php"><i class="bi bi-arrow-left me-1"></i> Back to Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation with password match check
        (function () {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    const newPassword = document.getElementById('new_password');
                    const confirmPassword = document.getElementById('confirm_password');
                    
                    // Check if passwords match
                    if (newPassword.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Passwords do not match');
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                    
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
                
                // Real-time password match validation
                const confirmPassword = document.getElementById('confirm_password');
                confirmPassword.addEventListener('input', function() {
                    const newPassword = document.getElementById('new_password');
                    if (this.value !== newPassword.value) {
                        this.setCustomValidity('Passwords do not match');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            });
        })();
    </script>
</body>
</html>