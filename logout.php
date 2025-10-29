<?php
// Initialize the session
session_start();

// Check if logout is confirmed
if (isset($_POST['confirm_logout']) && $_POST['confirm_logout'] === 'yes') {
    // Prevent caching (so "Back" button can't show old pages)
    header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
    header("Pragma: no-cache"); // HTTP 1.0
    header("Expires: 0"); // Proxies

    // Unset all session variables
    $_SESSION = array();

    // If you want to kill the session cookie as well
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy the session completely
    session_destroy();

    // Determine redirect location based on user type
    // Check if it was an admin or regular user
    $redirect_page = "index.html"; // Default redirect
    
    // You can add logic here to redirect to different pages
    // For example: if (isset($_SESSION['admin_loggedin'])) { $redirect_page = "admin.php"; }
    
    // Redirect to the appropriate page
    header("location: " . $redirect_page);
    exit;
}

// If logout is not confirmed, show confirmation page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Moya Water Delivery</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --moya-primary: #008080;
            --moya-light: #f8f9fa;
            --moya-dark-text: #34495e;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .logout-container {
            width: 100%;
            max-width: 480px;
        }
        
        .logout-card {
            background-color: #ffffff;
            border: none;
            border-radius: 1rem;
            padding: 3rem 2.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .logout-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(251, 191, 36, 0.4);
            }
            50% {
                box-shadow: 0 0 0 20px rgba(251, 191, 36, 0);
            }
        }
        
        .logout-icon i {
            font-size: 2.5rem;
            color: white;
        }
        
        .logout-card h2 {
            color: var(--moya-dark-text);
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }
        
        .logout-card p {
            color: #6c757d;
            font-size: 1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .btn-group-logout {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .btn {
            flex: 1;
            min-width: 140px;
            padding: 0.875rem 1.5rem;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            border: none;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }
        
        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background-color: #d1d5db;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .btn i {
            margin-right: 0.5rem;
        }
        
        /* Loading state */
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        /* Session info */
        .session-info {
            background-color: #f8fafc;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e2e8f0;
        }
        
        .session-info p {
            margin-bottom: 0.25rem;
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .session-info strong {
            color: var(--moya-dark-text);
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .logout-card {
                padding: 2rem 1.5rem;
            }
            
            .logout-card h2 {
                font-size: 1.5rem;
            }
            
            .btn-group-logout {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-card">
            <div class="logout-icon">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            
            <h2>Confirm Logout</h2>
            <p>Are you sure you want to log out?</p>
            
            <?php if (isset($_SESSION['admin_full_name'])): ?>
                <div class="session-info">
                    <p><strong>Logged in as:</strong> <?php echo htmlspecialchars($_SESSION['admin_full_name']); ?></p>
                    <p><strong>Account type:</strong> Administrator</p>
                </div>
            <?php elseif (isset($_SESSION['user_full_name'])): ?>
                <div class="session-info">
                    <p><strong>Logged in as:</strong> <?php echo htmlspecialchars($_SESSION['user_full_name']); ?></p>
                    <p><strong>Account type:</strong> Customer</p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="logout.php" id="logoutForm">
                <input type="hidden" name="confirm_logout" value="yes">
                <div class="btn-group-logout">
                    <button type="button" class="btn btn-secondary" onclick="goBack()">
                        <i class="bi"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger" id="logoutBtn">
                        <i class="bi"></i>Yes, Logout
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Go back to previous page
        function goBack() {
            window.history.back();
        }

        // Add loading state on logout
        document.getElementById('logoutForm').addEventListener('submit', function() {
            const btn = document.getElementById('logoutBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Logging out...';
        });

        // Prevent accidental page close
        window.addEventListener('beforeunload', function(e) {
            // This doesn't trigger for logout confirmation, only for accidental tab close
        });
    </script>
</body>
</html>