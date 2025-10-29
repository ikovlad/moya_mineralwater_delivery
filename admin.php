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
    $terms_accepted = isset($_POST["terms_accepted"]) ? true : false;

    if (empty($email) || empty($password)) {
        $login_err = "Email and password are required.";
    } elseif (!$terms_accepted) {
        $login_err = "You must accept the Terms & Conditions and Data Privacy Act to proceed.";
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
        
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #6c757d;
            transform: none;
        }
        
        .btn-primary:disabled:hover {
            box-shadow: none;
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
        
        /* Terms & Conditions Checkbox Styling */
        .terms-section {
            background-color: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-check {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .form-check-input {
            margin-top: 0.25rem;
            width: 1.125rem;
            height: 1.125rem;
            border: 2px solid var(--border-color);
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        
        .form-check-input:checked {
            background-color: var(--moya-primary);
            border-color: var(--moya-primary);
        }
        
        .form-check-input:focus {
            box-shadow: 0 0 0 3px rgba(0, 128, 128, 0.1);
        }
        
        .form-check-label {
            font-size: 0.875rem;
            color: #334155;
            line-height: 1.5;
            cursor: pointer;
        }
        
        .terms-link {
            color: var(--moya-primary);
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        
        .terms-link:hover {
            color: #006666;
            text-decoration: underline;
        }
        
        /* Modal Styling */
        .modal-header {
            background: linear-gradient(135deg, var(--moya-primary) 0%, #006666 100%);
            color: white;
            border-bottom: none;
            padding: 1.25rem 1.5rem;
        }
        
        .modal-title {
            font-weight: 600;
            font-size: 1.25rem;
        }
        
        .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .modal-body {
            padding: 1.5rem;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .modal-body h5 {
            color: var(--moya-primary);
            font-weight: 600;
            margin-top: 1rem;
            margin-bottom: 0.75rem;
            font-size: 1rem;
        }
        
        .modal-body h5:first-child {
            margin-top: 0;
        }
        
        .modal-body p, .modal-body ul {
            font-size: 0.9rem;
            color: #475569;
            line-height: 1.6;
        }
        
        .modal-body ul {
            padding-left: 1.5rem;
        }
        
        .modal-body li {
            margin-bottom: 0.5rem;
        }
        
        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 576px) {
            .login-card {
                padding: 2rem 1.5rem;
            }
            
            .login-card-header h1 {
                font-size: 1.5rem;
            }
            
            .terms-section {
                padding: 0.875rem;
            }
            
            .form-check-label {
                font-size: 0.8rem;
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

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" id="loginForm" novalidate>
                
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
                        <i class="bi"></i> Forgot Password?
                    </a>
                </div>

                <!-- Terms & Conditions Section -->
                <div class="terms-section">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="termsAccepted" name="terms_accepted" required>
                        <label class="form-check-label" for="termsAccepted">
                            I have read and agree to the 
                            <a href="#" class="terms-link" data-bs-toggle="modal" data-bs-target="#termsModal" onclick="event.preventDefault();">Terms & Conditions</a> 
                            and 
                            <a href="#" class="terms-link" data-bs-toggle="modal" data-bs-target="#privacyModal" onclick="event.preventDefault();">Data Privacy Act</a>
                        </label>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary" id="loginButton" disabled>
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </div>
                
            </form>
        </div>
    </div>

    <!-- Terms & Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">
                        <i class="bi bi-file-text me-2"></i>Terms & Conditions
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5>1. Acceptance of Terms</h5>
                    <p>By accessing and using the Moya Water Delivery Admin Panel, you accept and agree to be bound by the terms and provisions of this agreement.</p>

                    <h5>2. Administrator Responsibilities</h5>
                    <p>As an administrator, you agree to:</p>
                    <ul>
                        <li>Maintain the confidentiality of your login credentials</li>
                        <li>Use the system only for legitimate business purposes</li>
                        <li>Not share your account access with unauthorized persons</li>
                        <li>Report any security breaches immediately</li>
                        <li>Handle customer data with care and professionalism</li>
                    </ul>

                    <h5>3. Data Management</h5>
                    <p>Administrators must:</p>
                    <ul>
                        <li>Ensure accuracy of all data entered into the system</li>
                        <li>Process orders and customer information promptly</li>
                        <li>Maintain data integrity and security at all times</li>
                        <li>Follow company protocols for data backup and recovery</li>
                    </ul>

                    <h5>4. Prohibited Activities</h5>
                    <p>The following activities are strictly prohibited:</p>
                    <ul>
                        <li>Unauthorized access to system areas</li>
                        <li>Modification of system code or database without authorization</li>
                        <li>Sharing customer information with third parties</li>
                        <li>Using the system for personal gain or fraudulent purposes</li>
                        <li>Attempting to bypass security measures</li>
                    </ul>

                    <h5>5. Account Termination</h5>
                    <p>Moya reserves the right to terminate or suspend administrator access at any time for violations of these terms or for any reason deemed necessary for business operations.</p>

                    <h5>6. Limitation of Liability</h5>
                    <p>Moya shall not be liable for any indirect, incidental, or consequential damages arising from the use or inability to use the admin panel.</p>

                    <h5>7. Changes to Terms</h5>
                    <p>Moya reserves the right to modify these terms at any time. Continued use of the system after changes constitutes acceptance of the new terms.</p>

                    <h5>8. Governing Law</h5>
                    <p>These terms shall be governed by and construed in accordance with the laws of the Republic of the Philippines.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Privacy Act Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="privacyModalLabel">
                        <i class="bi bi-shield-check me-2"></i>Data Privacy Act Compliance
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5>Data Privacy Act of 2012 (RA 10173)</h5>
                    <p>Moya Water Delivery is committed to protecting the privacy and security of personal data in compliance with the Data Privacy Act of 2012.</p>

                    <h5>1. Data Collection and Processing</h5>
                    <p>As an administrator, you will have access to personal information including:</p>
                    <ul>
                        <li>Customer names, addresses, and contact information</li>
                        <li>Order history and transaction details</li>
                        <li>Payment information</li>
                        <li>Delivery preferences and schedules</li>
                    </ul>

                    <h5>2. Administrator Obligations</h5>
                    <p>You agree to:</p>
                    <ul>
                        <li>Process personal data only for legitimate business purposes</li>
                        <li>Implement appropriate security measures to protect data</li>
                        <li>Not disclose personal information to unauthorized parties</li>
                        <li>Report any data breaches immediately to management</li>
                        <li>Delete or anonymize data when no longer necessary</li>
                    </ul>

                    <h5>3. Data Subject Rights</h5>
                    <p>Customers have the right to:</p>
                    <ul>
                        <li>Access their personal information</li>
                        <li>Request correction of inaccurate data</li>
                        <li>Object to processing of their data</li>
                        <li>Request deletion of their data (subject to legal requirements)</li>
                        <li>Be informed of data breaches affecting their information</li>
                    </ul>

                    <h5>4. Security Measures</h5>
                    <p>The system implements:</p>
                    <ul>
                        <li>Encrypted data transmission and storage</li>
                        <li>Access controls and authentication</li>
                        <li>Regular security audits and updates</li>
                        <li>Activity logging and monitoring</li>
                    </ul>

                    <h5>5. Data Retention</h5>
                    <p>Personal data will be retained only for as long as necessary to fulfill business purposes or as required by law. Inactive accounts and completed orders will be archived or deleted according to company policy.</p>

                    <h5>6. Third-Party Sharing</h5>
                    <p>Customer data may be shared with third parties only when:</p>
                    <ul>
                        <li>Required by law or legal process</li>
                        <li>Necessary for service delivery (e.g., delivery partners)</li>
                        <li>Customer has provided explicit consent</li>
                    </ul>

                    <h5>7. Data Breach Protocol</h5>
                    <p>In the event of a data breach, administrators must:</p>
                    <ul>
                        <li>Immediately report the incident to management</li>
                        <li>Document the nature and extent of the breach</li>
                        <li>Cooperate with the investigation</li>
                        <li>Assist in notifying affected customers</li>
                    </ul>

                    <h5>8. Penalties for Non-Compliance</h5>
                    <p>Violations of the Data Privacy Act may result in:</p>
                    <ul>
                        <li>Immediate termination of access</li>
                        <li>Legal action and penalties</li>
                        <li>Criminal prosecution under RA 10173</li>
                    </ul>

                    <h5>Contact Information</h5>
                    <p>For questions regarding data privacy, contact the Data Protection Officer at: privacy@moya.com</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable/disable login button based on checkbox
        document.addEventListener('DOMContentLoaded', function() {
            const termsCheckbox = document.getElementById('termsAccepted');
            const loginButton = document.getElementById('loginButton');
            
            termsCheckbox.addEventListener('change', function() {
                loginButton.disabled = !this.checked;
            });

            // Prevent form submission if terms not accepted
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                if (!termsCheckbox.checked) {
                    e.preventDefault();
                    alert('You must accept the Terms & Conditions and Data Privacy Act to proceed.');
                }
            });
        });
    </script>

</body>
</html>