<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moya - Admin Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --moya-primary: #008080;
            --moya-light: #f5fcfc;
            --moya-dark-text: #34495e;
            --sidebar-width: 280px;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--moya-light);
            color: var(--moya-dark-text);
        }

        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        /* --- Sidebar Styles --- */
        #sidebar {
            min-width: var(--sidebar-width);
            max-width: var(--sidebar-width);
            background: var(--moya-primary);
            color: #fff;
            transition: all 0.3s;
            position: fixed;
            height: 100%;
            z-index: 1000;
        }

        #sidebar .sidebar-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        #sidebar .sidebar-header h3 {
            font-weight: 700;
        }
        
        #sidebar .nav-item .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 15px 25px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        #sidebar .nav-item .nav-link:hover,
        #sidebar .nav-item .nav-link.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            border-left: 4px solid #fff;
            padding-left: 21px;
        }
        
        #sidebar .nav-item .nav-link i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }
        
        /* --- Main Content Styles --- */
        #main-content {
            width: calc(100% - var(--sidebar-width));
            padding: 2rem;
            min-height: 100vh;
            transition: all 0.3s;
            margin-left: var(--sidebar-width);
        }

        .stat-card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .card-body {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-card .stat-icon {
            font-size: 2.5rem;
            padding: 1rem;
            border-radius: 50%;
            color: #fff;
        }

        .data-table-card {
             border: none;
             border-radius: 0.75rem;
             box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        /* Responsive Styles */
        @media (max-width: 992px) {
            #sidebar {
                margin-left: -var(--sidebar-width);
            }
            #sidebar.active {
                margin-left: 0;
            }
            #main-content {
                width: 100%;
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3>Moya Admin</h3>
        </div>

        <ul class="nav flex-column p-3">
            <li class="nav-item">
                <a class="nav-link active" href="#">
                    <i class="bi bi-grid-fill"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-receipt"></i>Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-box-seam-fill"></i>Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-people-fill"></i>Customers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="bi bi-bar-chart-fill"></i>Reports
                </a>
            </li>
            <li class="nav-item mt-auto pt-5">
                 <a class="nav-link" href="../logout.php">
                    <i class="bi bi-box-arrow-left"></i>Log Out
                </a>
            </li>
        </ul>
    </nav>

    <div id="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 fw-bold">Dashboard Overview</h1>
            <div class="d-flex align-items-center">
                <span class="me-3">Welcome, **Admin Name**</span>
                <a href="../logout.php" class="btn btn-outline-danger">Log Out</a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div>
                            <h5 class="card-title text-muted fw-normal">Total Orders</h5>
                            <h2 class="fw-bold">1,254</h2>
                        </div>
                        <div class="stat-icon bg-primary"><i class="bi bi-receipt"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                 <div class="card stat-card">
                    <div class="card-body">
                        <div>
                            <h5 class="card-title text-muted fw-normal">Pending</h5>
                            <h2 class="fw-bold">32</h2>
                        </div>
                        <div class="stat-icon bg-warning"><i class="bi bi-hourglass-split"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                 <div class="card stat-card">
                    <div class="card-body">
                        <div>
                            <h5 class="card-title text-muted fw-normal">Revenue</h5>
                            <h2 class="fw-bold">₱45,800</h2>
                        </div>
                        <div class="stat-icon bg-success"><i class="bi bi-cash-coin"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                 <div class="card stat-card">
                    <div class="card-body">
                        <div>
                            <h5 class="card-title text-muted fw-normal">Customers</h5>
                            <h2 class="fw-bold">189</h2>
                        </div>
                        <div class="stat-icon bg-info"><i class="bi bi-people-fill"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card data-table-card">
            <div class="card-header bg-white border-0">
                <h4 class="fw-bold">Recent Orders</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col"># ID</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Product</th>
                                <th scope="col">Total (₱)</th>
                                <th scope="col">Status</th>
                                <th scope="col">Order Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row">#1254</th>
                                <td>Juan Dela Cruz</td>
                                <td>5-Gallon Purified</td>
                                <td class="fw-bold text-success">₱35.00</td>
                                <td><span class="badge bg-success">Delivered</span></td>
                                <td>Oct 19, 2025</td>
                            </tr>
                            <tr>
                                <th scope="row">#1253</th>
                                <td>Maria Clara</td>
                                <td>5-Gallon Mineral</td>
                                <td class="fw-bold text-success">₱40.00</td>
                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                                <td>Oct 19, 2025</td>
                            </tr>
                            <tr>
                                <th scope="row">#1252</th>
                                <td>Jose Rizal</td>
                                <td>5-Gallon Alkaline</td>
                                <td class="fw-bold text-success">₱50.00</td>
                                <td><span class="badge bg-info text-dark">Processing</span></td>
                                <td>Oct 18, 2025</td>
                            </tr>
                             <tr>
                                <th scope="row">#1251</th>
                                <td>Andres Bonifacio</td>
                                <td>5-Gallon Purified</td>
                                <td class="fw-bold text-success">₱35.00</td>
                                <td><span class="badge bg-danger">Cancelled</span></td>
                                <td>Oct 18, 2025</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>