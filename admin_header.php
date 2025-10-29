<?php
session_start();
<<<<<<< HEAD

=======
>>>>>>> 81caf45 (try)
// Redirect if not logged in as admin
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: admin.php");
    exit;
}
<<<<<<< HEAD
=======

// Get current page to highlight active nav item
$current_page = basename($_SERVER['PHP_SELF']);
>>>>>>> 81caf45 (try)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moya Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="admin_styles.css">
<<<<<<< HEAD
</head>
<body>
    <div class="sidebar">
        <h3 class="sidebar-header">Delivery Management System</h3>
        <p class="sidebar-paragraph">Moya Mineral Water</pclass></p>
        
        <!--
            FIX: The <ul> is now structured with two divs.
            - '.sidebar-nav' holds the main links.
            - '.logout-section' holds the logout link.
            This allows the CSS to push the logout link to the bottom, fixing the whitespace issue.
        -->
        <ul class="nav flex-column">
            <div class="sidebar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="admin_dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_users.php"><i class="bi bi-people-fill"></i> User Management</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_orders.php"><i class="bi bi-box-seam-fill"></i> Order Management</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_sales.php"><i class="bi bi-bar-chart-line-fill"></i> Sales & Order History</a>
                </li>
            </div>
            <div class="logout-section">
                <li class="nav-item">
                    <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a>
                </li>
            </div>
        </ul>
    </div>
    
    <!-- The main-content div is opened here and closed in admin_footer.php -->
    <div class="main-content">

=======
    <style>
        /* Enhanced Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            padding: 0;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #f1f3f5;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 10px;
        }

        .sidebar-brand {
            padding: 1.5rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
            position: relative;
        }

        .sidebar-brand h3 {
            color: var(--moya-primary);
            margin: 0 0 0.25rem 0;
            font-size: 1.125rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .sidebar-brand p {
            color: #858796;
            margin: 0;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Mobile close button */
        .sidebar-close {
            display: none;
            position: absolute;
            top: 1.25rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #858796;
            cursor: pointer;
            padding: 0.25rem;
            line-height: 1;
            transition: color 0.2s ease;
        }

        .sidebar-close:hover {
            color: var(--moya-primary);
        }

        .sidebar-nav {
            flex: 1;
            padding: 1rem 0;
        }

        .nav-section-title {
            padding: 1rem 1.5rem 0.5rem 1.5rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #858796;
            margin-top: 0.5rem;
        }

        .sidebar .nav-link {
            color: var(--moya-dark-text);
            font-weight: 500;
            font-size: 0.9rem;
            padding: 0.75rem 1.5rem;
            margin: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            position: relative;
        }

        .sidebar .nav-link i {
            margin-right: 0.875rem;
            font-size: 1rem;
            width: 20px;
            text-align: center;
            color: #8895a7;
            transition: color 0.2s ease;
        }

        .sidebar .nav-link:hover {
            background-color: rgba(0, 128, 128, 0.05);
            color: var(--moya-primary);
            transform: translateX(3px);
        }

        .sidebar .nav-link:hover i {
            color: var(--moya-primary);
        }

        .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--moya-primary) 0%, #006666 100%);
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0, 128, 128, 0.2);
        }

        .sidebar .nav-link.active i {
            color: #ffffff;
        }

        .sidebar .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 70%;
            background-color: #ffffff;
            border-radius: 0 3px 3px 0;
        }

        .sidebar-footer {
            border-top: 1px solid var(--border-color);
            padding: 1rem 0;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
        }

        .admin-profile {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0 0.75rem 0.5rem 0.75rem;
            background-color: rgba(0, 128, 128, 0.05);
            border-radius: 0.5rem;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--moya-primary) 0%, #006666 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .admin-info {
            flex: 1;
            min-width: 0;
        }

        .admin-name {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--moya-dark-text);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .admin-role {
            font-size: 0.75rem;
            color: #858796;
            margin: 0;
        }

        .logout-link {
            color: #e74a3b !important;
        }

        .logout-link:hover {
            background-color: rgba(231, 74, 59, 0.05) !important;
            color: #c0392b !important;
        }

        .logout-link i {
            color: #e74a3b !important;
        }

        .logout-link:hover i {
            color: #c0392b !important;
        }

        /* Mobile Menu Toggle Button */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: linear-gradient(135deg, var(--moya-primary) 0%, #006666 100%);
            color: white;
            border: none;
            border-radius: 0.5rem;
            padding: 0.625rem 0.875rem;
            font-size: 1.25rem;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 128, 128, 0.3);
            transition: all 0.2s ease;
        }

        .mobile-menu-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 128, 128, 0.4);
        }

        .mobile-menu-toggle:active {
            transform: translateY(0);
        }

        /* Hide hamburger button when sidebar is open */
        .mobile-menu-toggle.hidden {
            opacity: 0;
            pointer-events: none;
            transform: scale(0.8);
        }

        /* Sidebar Overlay for Mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.show {
            opacity: 1;
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar-close {
                display: block;
            }

            .sidebar-brand {
                padding-right: 3rem;
            }

            .sidebar-brand h3 {
                font-size: 1rem;
            }

            .sidebar-brand p {
                font-size: 0.75rem;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .sidebar-overlay {
                display: block;
            }

            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding-top: 4rem !important; /* Add padding to prevent overlap with hamburger button */
            }

            /* Adjust page headers on mobile to not overlap with button */
            .page-header {
                margin-top: 0.5rem;
            }

            /* Adjust navigation items for mobile */
            .sidebar .nav-link {
                font-size: 0.875rem;
                padding: 0.7rem 1.25rem;
            }

            .nav-section-title {
                font-size: 0.65rem;
                padding: 0.875rem 1.25rem 0.5rem 1.25rem;
            }

            .admin-profile {
                padding: 0.65rem 1.25rem;
            }

            .admin-avatar {
                width: 36px;
                height: 36px;
                font-size: 0.9rem;
            }
        }

        /* Tablet responsiveness */
        @media (min-width: 769px) and (max-width: 992px) {
            .sidebar {
                width: 220px;
            }

            .sidebar-brand h3 {
                font-size: 1rem;
            }

            .sidebar .nav-link {
                font-size: 0.85rem;
                padding: 0.7rem 1.25rem;
            }

            .nav-section-title {
                font-size: 0.65rem;
            }
        }

        /* Prevent body scroll when mobile menu is open */
        body.sidebar-open {
            overflow: hidden;
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle Button -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
        <i class="bi bi-list"></i>
    </button>

    <!-- Sidebar Overlay (for mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <button class="sidebar-close" id="sidebarClose" aria-label="Close menu">
                <i class="bi bi-x-lg"></i>
            </button>
            <h3>Delivery Management System</h3>
            <p>Moya Mineral Water</p>
        </div>
        
        <div class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-section-title">MAIN MENU</li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>" href="admin_dashboard.php">
                        <i class="bi bi-grid-1x2-fill"></i> Dashboard
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'admin_users.php') ? 'active' : ''; ?>" href="admin_users.php">
                        <i class="bi bi-people-fill"></i> User Management
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'admin_orders.php') ? 'active' : ''; ?>" href="admin_orders.php">
                        <i class="bi bi-box-seam-fill"></i> Order Management
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'admin_sales.php') ? 'active' : ''; ?>" href="admin_sales.php">
                        <i class="bi bi-bar-chart-line-fill"></i> Sales & Order History
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="sidebar-footer">
            <div class="admin-profile">
                <div class="admin-avatar">
                    <?php 
                    $admin_name = $_SESSION["admin_full_name"] ?? 'Admin';
                    echo strtoupper(substr($admin_name, 0, 1)); 
                    ?>
                </div>
                <div class="admin-info">
                    <p class="admin-name"><?php echo htmlspecialchars($admin_name); ?></p>
                    <p class="admin-role">Administrator</p>
                </div>
            </div>
            
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link logout-link" href="logout.php">
                        <i class="bi bi-box-arrow-left"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- JavaScript for Mobile Menu -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebarClose = document.getElementById('sidebarClose');
            const body = document.body;

            // Function to open sidebar
            function openSidebar() {
                sidebar.classList.add('show');
                sidebarOverlay.classList.add('show');
                body.classList.add('sidebar-open');
                mobileMenuToggle.classList.add('hidden'); // Hide hamburger button
            }

            // Function to close sidebar
            function closeSidebar() {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
                body.classList.remove('sidebar-open');
                mobileMenuToggle.classList.remove('hidden'); // Show hamburger button
            }

            // Toggle sidebar on mobile menu button click
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', openSidebar);
            }

            // Close sidebar on close button click
            if (sidebarClose) {
                sidebarClose.addEventListener('click', closeSidebar);
            }

            // Close sidebar on overlay click
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeSidebar);
            }

            // Close sidebar when clicking on a nav link (mobile only)
            const navLinks = document.querySelectorAll('.sidebar .nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        closeSidebar();
                    }
                });
            });

            // Handle window resize
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    if (window.innerWidth > 768) {
                        closeSidebar();
                    }
                }, 250);
            });

            // Prevent body scroll when sidebar is open on mobile
            sidebar.addEventListener('touchmove', function(e) {
                if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
                    e.stopPropagation();
                }
            }, { passive: true });
        });
    </script>
    
    <!-- The main-content div is opened here and closed in admin_footer.php -->
    <div class="main-content">
>>>>>>> 81caf45 (try)
