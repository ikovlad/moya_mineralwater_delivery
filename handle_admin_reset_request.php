<?php
// handle_admin_reset_request.php
session_start();
require_once "config.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$_SESSION['admin_reset_message'] = "An unexpected error occurred processing your request.";
$_SESSION['admin_reset_message_type'] = "danger";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['admin_reset_message'] = "Please enter a valid email address.";
    } else {
        $sql_check = "SELECT id, full_name FROM admins WHERE email = ?";
        $admin_id = null;
        $admin_name = null;

        if ($stmt_check = $conn->prepare($sql_check)) {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows == 1) {
                $stmt_check->bind_result($admin_id, $admin_name);
                $stmt_check->fetch();
                
                try {
                    $token = bin2hex(random_bytes(32));
                    $token_hash = password_hash($token, PASSWORD_DEFAULT);
                    $expiry = new DateTime('now', new DateTimeZone(date_default_timezone_get()));
                    $expiry->add(new DateInterval('PT1H'));
                    $expires_at = $expiry->format('Y-m-d H:i:s');

                    $sql_update = "UPDATE admins SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ?";
                    if ($stmt_update = $conn->prepare($sql_update)) {
                        $stmt_update->bind_param("ssi", $token_hash, $expires_at, $admin_id);
                        if ($stmt_update->execute()) {
                            
                            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                            $host = $_SERVER['HTTP_HOST'];
                            $script_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                            $base_url = $protocol . $host . ($script_dir == '/' ? '' : $script_dir);
                            $reset_link = $base_url . "/admin_reset_password.php?token=" . urlencode($token) . "&email=" . urlencode($email);

                            $subject = "Admin Password Reset Request for Moya Delivery";
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
                                        background-color: #008080;
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
                                    <div class='header'>Moya Admin Password Reset</div>
                                    <div class='content'>
                                        <p>Hello " . htmlspecialchars($admin_name) . ",</p>
                                        <p>We received a request to reset your admin password. If you did not make this request, please ignore this email.</p>
                                        <p>To reset your password, please click the button below. This link is valid for <strong>1 hour</strong>.</p>
                                        
                                        <a href='" . htmlspecialchars($reset_link) . "' class='button'>Reset Your Admin Password</a>
                                        
                                        <p>If you cannot click the button, please copy and paste the following link into your browser:</p>
                                        <p><a href='" . htmlspecialchars($reset_link) . "'>" . htmlspecialchars($reset_link) . "</a></p>
                                    </div>
                                    <div class='footer'>
                                        <p>&copy; " . date("Y") . " Moya Delivery. All rights reserved.</p>
                                    </div>
                                </div>
                            </body>
                            </html>";

                            $mail = new PHPMailer(true);
                            $email_sent_successfully = false;

                            try {
                                $mail->isSMTP();
                                $mail->Host       = 'smtp.gmail.com';
                                $mail->SMTPAuth   = true;
                                $mail->Username   = 'mime.neri.up@phinmaed.com';
                                $mail->Password   = 'crsn pelr idui gxdr';
                                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                                $mail->Port       = 465;
                                
                                $mail->setFrom('no-reply@moyadelivery.com', 'Moya Delivery');
                                $mail->addAddress($email, $admin_name);
                                $mail->isHTML(true);
                                $mail->Subject = $subject;
                                $mail->Body    = $body;
                                $mail->AltBody = "Hello " . htmlspecialchars($admin_name) . ",\n\nTo reset your admin password, please visit the following link (valid for 1 hour):\n" . $reset_link . "\n\nIf you did not request this, please ignore this email.";

                                $mail->send();
                                $email_sent_successfully = true;

                                if ($email_sent_successfully) {
                                     $_SESSION['admin_reset_message'] = "Password reset instructions have been sent to " . htmlspecialchars($email) . ". Please check your inbox (and spam folder).";
                                     $_SESSION['admin_reset_message_type'] = "success";
                                } else {
                                     $_SESSION['admin_reset_message'] = "Could not send reset email due to server configuration. Please contact support.";
                                     $_SESSION['admin_reset_message_type'] = "warning";
                                     error_log("Email sending likely failed for $email. Check server mail logs/config.");
                                }

                            } catch (Exception $e) {
                                error_log("PHPMailer Error sending to " . $email . ": " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
                                $_SESSION['admin_reset_message'] = "Could not send password reset email. Please try again later or contact support.";
                                $_SESSION['admin_reset_message_type'] = "danger";
                            }

                        } else {
                             error_log("DB Update Error (handle_admin_reset_request): " . $stmt_update->error);
                            $_SESSION['admin_reset_message'] = "Error saving reset request. Please try again.";
                        }
                        $stmt_update->close();
                    } else {
                         error_log("DB Prepare Error (handle_admin_reset_request - update): " . $conn->error);
                        $_SESSION['admin_reset_message'] = "Error preparing reset request.";
                    }
                } catch (Exception $e) {
                    error_log("Token/Date generation error: " . $e->getMessage());
                    $_SESSION['admin_reset_message'] = "Error generating secure reset token.";
                }

            } else {
                $_SESSION['admin_reset_message'] = "If an account exists for " . htmlspecialchars($email) . ", password reset instructions have been sent. Please check your inbox (and spam folder).";
                $_SESSION['admin_reset_message_type'] = "success";
            }
            $stmt_check->close();
        } else {
            error_log("DB Prepare Error (handle_admin_reset_request - check): " . $conn->error);
             $_SESSION['admin_reset_message'] = "Error checking your email address.";
        }
    }
} else {
    $_SESSION['admin_reset_message'] = "Invalid request method.";
}

$conn->close();
header("location: admin_forgot_password.php");
exit;
?>