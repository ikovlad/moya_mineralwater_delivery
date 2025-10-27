<?php
// handle_reset_request.php
session_start();
require_once "config.php"; // Ensure timezone 'Asia/Manila' is set in config.php

// --- PHPMailer Setup ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Adjust path if your PHPMailer folder has a different name or location
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
// --- End PHPMailer Setup ---

// Default message in case something goes wrong early
$_SESSION['reset_message'] = "An unexpected error occurred processing your request.";
$_SESSION['reset_message_type'] = "danger";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['reset_message'] = "Please enter a valid email address.";
    } else {
        // Check if email exists
        $sql_check = "SELECT id, full_name FROM users WHERE email = ?";
        $user_id = null;
        $user_full_name = null;

        if ($stmt_check = $conn->prepare($sql_check)) {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows == 1) {
                $stmt_check->bind_result($user_id, $user_full_name);
                $stmt_check->fetch();
                // --- User found, generate token ---
                try {
                    $token = bin2hex(random_bytes(32)); // Secure token
                    $token_hash = password_hash($token, PASSWORD_DEFAULT); // Hash for DB storage
                    $expiry = new DateTime('now', new DateTimeZone(date_default_timezone_get())); // Use timezone from config
                    $expiry->add(new DateInterval('PT1H')); // 1 Hour expiry
                    $expires_at = $expiry->format('Y-m-d H:i:s');

                    // Update user record with hashed token and expiry
                    $sql_update = "UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ?";
                    if ($stmt_update = $conn->prepare($sql_update)) {
                        $stmt_update->bind_param("ssi", $token_hash, $expires_at, $user_id);
                        if ($stmt_update->execute()) {
                            // --- Token saved, ATTEMPT TO SEND EMAIL ---

                            // Construct the FULL reset link including the domain
                            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                            $host = $_SERVER['HTTP_HOST']; // Gets your domain (e.g., moyadelivery.great-site.net)
                            $script_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                            $base_url = $protocol . $host . ($script_dir == '/' ? '' : $script_dir); // Handle root case
                            $reset_link = $base_url . "/reset_password.php?token=" . urlencode($token) . "&email=" . urlencode($email);

                            // --- FIXED: ADDED EMAIL SUBJECT AND BODY ---
                            $subject = "Password Reset Request for Moya Delivery";
                            $body = "
                            <html>
                            <head>
                                <style>
                                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                                    .container { width: 90%; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                                    .header { font-size: 24px; color: #333; }
                                    .content { margin-top: 20px; }
                                    .button { 
                                        display: inline-block; 
                                        padding: 12px 20px; 
                                        margin: 20px 0; 
                                        background-color: #0d6efd; /* Bootstrap Primary */
                                        color: #ffffff !important; 
                                        text-decoration: none; 
                                        border-radius: 5px; 
                                        font-weight: bold;
                                    }
                                    .footer { margin-top: 20px; font-size: 12px; color: #888; }
                                </style>
                            </head>
                            <body>
                                <div class='container'>
                                    <div class='header'>Moya Delivery Password Reset</div>
                                    <div class='content'>
                                        <p>Hello " . htmlspecialchars($user_name) . ",</p>
                                        <p>We received a request to reset your password. If you did not make this request, please ignore this email.</p>
                                        <p>To reset your password, please click the button below. This link is valid for <strong>1 hour</strong>.</p>
                                        
                                        <a href='" . htmlspecialchars($reset_link) . "' class='button'>Reset Your Password</a>
                                        
                                        <p>If you cannot click the button, please copy and paste the following link into your browser:</p>
                                        <p><a href='" . htmlspecialchars($reset_link) . "'>" . htmlspecialchars($reset_link) . "</a></p>
                                    </div>
                                    <div class='footer'>
                                        <p>&copy; " . date("Y") . " Moya Delivery. All rights reserved.</p>
                                    </div>
                                </div>
                            </body>
                            </html>";
                            // --- END OF EMAIL BODY ---


                            $mail = new PHPMailer(true); // Enable exceptions for error handling
                            $email_sent_successfully = false; // Flag to check success

                            try {
                                $mail->isSMTP();
                                $mail->Host       = 'smtp.gmail.com';
                                $mail->SMTPAuth   = true;
                                $mail->Username   = 'mime.neri.up@phinmaed.com'; // Your full Gmail
                                // !! IMPORTANT: GO TO YOUR GOOGLE ACCOUNT -> SECURITY -> 2-STEP VERIFICATION -> APP PASSWORDS
                                // !! GENERATE A 16-CHARACTER PASSWORD AND PASTE IT BELOW
                                $mail->Password   = 'crsn pelr idui gxdr'; // <-- PASTE YOUR 16-CHARACTER APP PASSWORD HERE
                                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                                $mail->Port       = 465;
                                
                                // --- FIXED: ADDED RECIPIENT AND CONTENT ---
                                $mail->setFrom('no-reply@moyadelivery.com', 'Moya Delivery'); // Set a 'From' address
                                $mail->addAddress($email, $user_name);     // Add the recipient
                                $mail->isHTML(true);                            // Set email format to HTML
                                $mail->Subject = $subject;
                                $mail->Body    = $body;
                                $mail->AltBody = "Hello " . htmlspecialchars($user_name) . ",\n\nTo reset your password, please visit the following link (valid for 1 hour):\n" . $reset_link . "\n\nIf you did not request this, please ignore this email.";
                                // --- END OF FIXED SECTION ---


                                // ** Method 2: Other SMTP (e.g., SendGrid, Mailgun, Business Email) **
                                /*
                                $mail->isSMTP();
                                $mail->Host       = 'your_smtp_host.com';     // e.g., smtp.mailgun.org
                                $mail->SMTPAuth   = true;
                                $mail->Username   = 'your_smtp_username'; // Provided by your service
                                $mail->Password   = 'your_smtp_password'; // Provided by your service
                                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Usually TLS
                                $mail->Port       = 587;                          // Usually 587 for TLS
                                */

                                // ** FIXED: UNCOMMENTED $mail->send() **
                                $mail->send();

                                // ** If $mail->send() DOES NOT throw an error, assume success **
                                $email_sent_successfully = true; // Set flag if send() didn't crash

                                // --- User Feedback ---
                                if ($email_sent_successfully) {
                                     $_SESSION['reset_message'] = "Password reset instructions have been sent to " . htmlspecialchars($email) . ". Please check your inbox (and spam folder).";
                                     $_SESSION['reset_message_type'] = "success";
                                } else {
                                     // This block is less likely to be hit if exceptions are on, but good as a fallback
                                     $_SESSION['reset_message'] = "Could not send reset email due to server configuration. Please contact support.";
                                     $_SESSION['reset_message_type'] = "warning";
                                     error_log("Email sending likely failed for $email. Check server mail logs/config.");
                                }


                            } catch (Exception $e) {
                                // Email sending FAILED and threw an exception (e.g., SMTP connection error)
                                error_log("PHPMailer Error sending to " . $email . ": " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
                                $_SESSION['reset_message'] = "Could not send password reset email. Please try again later or contact support.";
                                $_SESSION['reset_message_type'] = "danger";
                            }
                            // --- END OF EMAIL LOGIC ---

                        } else { /* Handle DB update execution error */
                             error_log("DB Update Error (handle_reset_request): " . $stmt_update->error);
                            $_SESSION['reset_message'] = "Error saving reset request. Please try again.";
                        }
                        $stmt_update->close();
                    } else { /* Handle DB update prepare error */
                         error_log("DB Prepare Error (handle_reset_request - update): " . $conn->error);
                        $_SESSION['reset_message'] = "Error preparing reset request.";
                    }
                } catch (Exception $e) { /* Handle token/date generation error */
                    error_log("Token/Date generation error: " . $e->getMessage());
                    $_SESSION['reset_message'] = "Error generating secure reset token.";
                }

            } else {
                // User not found - Show the SAME vague success message for security
                $_SESSION['reset_message'] = "If an account exists for " . htmlspecialchars($email) . ", password reset instructions have been sent. Please check your inbox (and spam folder).";
                $_SESSION['reset_message_type'] = "success"; // Still show success-like message
            }
            $stmt_check->close();
        } else { /* Handle DB check prepare error */
            error_log("DB Prepare Error (handle_reset_request - check): " . $conn->error);
             $_SESSION['reset_message'] = "Error checking your email address.";
        }
    }
} else {
    $_SESSION['reset_message'] = "Invalid request method.";
}

$conn->close();
header("location: forgot_password.php"); // Always redirect back
exit;
?>