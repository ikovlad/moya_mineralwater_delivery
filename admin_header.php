<?php
session_start();

// Redirect if not logged in as admin
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: admin.php");
    exit;
}
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

