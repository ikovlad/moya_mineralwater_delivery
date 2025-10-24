<?php
// Initialize the session and include config
session_start();
require_once "config.php";

// Disable caching to ensure data is fresh
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.html");
    exit;
}

$user_id = $_SESSION["id"];
$user_name = htmlspecialchars($_SESSION["full_name"]);
$user_barangay = htmlspecialchars($_SESSION["address_barangay"]);

// --- 1. Fetch Product Data (ID and Price) from Database ---
$product_data = [];
$product_names = [
    'Standard Round Refill', 
    'Slim Container Refill', 
    'New Standard Round', 
    'New Slim Container'
];
$product_names_str = "'" . implode("','", array_map([$conn, 'real_escape_string'], $product_names)) . "'";

$sql_prices = "SELECT id, name, price FROM products WHERE name IN ($product_names_str)";
$result_prices = mysqli_query($conn, $sql_prices);

if ($result_prices) {
    while ($row = mysqli_fetch_assoc($result_prices)) {
        $product_data[$row['name']] = [
            'id' => $row['id'],
            'price' => (float)$row['price']
        ];
    }
    mysqli_free_result($result_prices);
}

$price_refill = $product_data['Standard Round Refill']['price'] ?? 40.00;
$price_new_container = $product_data['New Standard Round']['price'] ?? 250.00;

$id_refill_round = $product_data['Standard Round Refill']['id'] ?? 0;
$id_refill_slim = $product_data['Slim Container Refill']['id'] ?? 0;
$id_new_round = $product_data['New Standard Round']['id'] ?? 0;
$id_new_slim = $product_data['New Slim Container']['id'] ?? 0;

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Your Order | Moya Water Delivery</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root { 
            --moya-primary: #008080;
            --moya-secondary: #00bfff;
            --moya-light: #f5fcfc;
            --moya-dark: #333;
            --bs-primary: var(--moya-primary);
            --bs-primary-rgb: 0, 128, 128;
        }
        
        body { 
            font-family: 'Lato', sans-serif; 
            background-image: url(img/bg.svg);
            background-color: var(--moya-light);
            color: #1f2937;
        }
        
        h1, h2, h3 {
            font-family: 'Bricolage Grotesque', sans-serif;
        }
        
        /* Navbar Styles */
        .navbar {
            background-color: #fff !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .nav-link {
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--moya-primary) !important;
        }
        
        #profileDropdown {
            background-color: var(--moya-primary) !important;
            border-color: var(--moya-primary) !important;
        }
        
        #profileDropdown:hover {
            background-color: #006666 !important;
            border-color: #006666 !important;
        }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--moya-primary) 0%, var(--moya-secondary) 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .page-header .lead {
            font-size: 1.1rem;
            opacity: 0.95;
        }
        
        /* Product Cards */
        .product-card { 
            transition: all 0.3s ease; 
            border: 2px solid #e5e7eb;
            background-color: #fff;
            height: 100%;
        }
        
        .product-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); 
        }
        
        .product-card.active-primary {
            border-color: var(--moya-primary);
            box-shadow: 0 6px 15px rgba(0, 128, 128, 0.25);
            background-color: #f0fffe;
        }
        
        .product-card.active-secondary {
            border-color: var(--moya-secondary);
            box-shadow: 0 6px 15px rgba(0, 191, 255, 0.25);
            background-color: #f0f9ff;
        }
        
        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--moya-primary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid var(--moya-primary);
            display: inline-block;
        }
        
        .price-badge {
            font-size: 1.5rem;
            padding: 0.5rem 1rem;
        }
        
        .qty-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .qty-input { 
            width: 70px; 
            text-align: center; 
            border: 2px solid #e5e7eb;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .qty-input:focus {
            box-shadow: none;
            border-color: var(--moya-primary);
        }
        
        .btn-qty {
            width: 36px;
            height: 36px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 700;
            border-width: 2px;
        }
        
        /* Summary Card */
        .summary-card {
            background-color: #fff;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 100px;
        }
        
        .summary-header {
            background: linear-gradient(135deg, var(--moya-primary) 0%, var(--moya-secondary) 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 1rem 1rem 0 0;
            margin: -1rem -1rem 1rem -1rem;
        }
        
        .summary-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .summary-total {
            background-color: #f9fafb;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn-place-order {
            background: linear-gradient(135deg, var(--moya-primary) 0%, var(--moya-secondary) 100%);
            border: none;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 1rem;
            box-shadow: 0 4px 12px rgba(0, 128, 128, 0.3);
            transition: all 0.3s ease;
        }
        
        .btn-place-order:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 128, 128, 0.4);
            background: linear-gradient(135deg, #006666 0%, #0099cc 100%);
        }
        
        .btn-place-order:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
        }
        
        .card-shadow { 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); 
        }
        
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .summary-card {
                position: static;
            }
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container py-2">
        <a class="navbar-brand d-flex align-items-center gap-2" href="home.php">
            <img src="img/moya_logo.png" alt="Moya Logo" style="height: 50px; width: auto;">
            <span class="text-primary">Moya</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link" href="home.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="home.php#products">Containers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="home.php#process">Delivery</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="home.php#location">Area</a>
                </li>
                <li class="nav-item dropdown ms-lg-3">
                    <a class="nav-link dropdown-toggle btn btn-primary rounded-pill px-4 text-white" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo $user_name; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="order.php">Order Here</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger fw-semibold" href="logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Place Your Order</h1>
        <p class="lead mb-0">Delivering to <strong><?php echo $user_barangay; ?></strong> • Select your quantities below</p>
    </div>
</div>

<div class="container pb-5">
    <div class="row">
        <!-- Products Column -->
        <div class="col-lg-8 mb-4">
            <form id="orderForm" action="place_order.php" method="POST">
                
                <!-- Hidden inputs for product IDs -->
                <input type="hidden" name="product_ids[<?php echo $id_refill_round; ?>]" value="refill_round_qty">
                <input type="hidden" name="product_ids[<?php echo $id_refill_slim; ?>]" value="refill_slim_qty">
                <input type="hidden" name="product_ids[<?php echo $id_new_round; ?>]" value="new_round_qty">
                <input type="hidden" name="product_ids[<?php echo $id_new_slim; ?>]" value="new_slim_qty">

                <!-- Water Refills Section -->
                <div class="mb-5">
                    <h3 class="section-title">Water Refills</h3>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card p-4 rounded-4 product-card card-shadow" id="cardRefillRound">
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div>
                                        <h4 class="h5 fw-bold mb-1">Standard Round Refill</h4>
                                        <p class="text-muted small mb-0">For 5-gallon round containers</p>
                                    </div>
                                    <span class="badge bg-primary price-badge">₱<?php echo number_format($price_refill, 2); ?></span>
                                </div>
                                
                                <div class="qty-controls mt-auto">
                                    <button class="btn btn-outline-primary btn-qty btn-minus" type="button" data-target="refill_round_qty">−</button>
                                    <input type="number" class="form-control qty-input" name="refill_round_qty" id="refill_round_qty" value="0" min="0" readonly>
                                    <button class="btn btn-outline-primary btn-qty btn-plus" type="button" data-target="refill_round_qty">+</button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card p-4 rounded-4 product-card card-shadow" id="cardRefillSlim">
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div>
                                        <h4 class="h5 fw-bold mb-1">Slim Container Refill</h4>
                                        <p class="text-muted small mb-0">For slim containers with faucet</p>
                                    </div>
                                    <span class="badge bg-primary price-badge">₱<?php echo number_format($price_refill, 2); ?></span>
                                </div>
                                
                                <div class="qty-controls mt-auto">
                                    <button class="btn btn-outline-primary btn-qty btn-minus" type="button" data-target="refill_slim_qty">−</button>
                                    <input type="number" class="form-control qty-input" name="refill_slim_qty" id="refill_slim_qty" value="0" min="0" readonly>
                                    <button class="btn btn-outline-primary btn-qty btn-plus" type="button" data-target="refill_slim_qty">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Containers Section -->
                <div class="mb-4">
                    <h3 class="section-title" style="border-bottom-color: var(--moya-secondary);">New Containers</h3>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card p-4 rounded-4 product-card card-shadow" id="cardNewRound">
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div>
                                        <h4 class="h5 fw-bold mb-1">New Standard Round</h4>
                                        <p class="text-muted small mb-0">Container + first refill included</p>
                                    </div>
                                    <span class="badge bg-secondary price-badge">₱<?php echo number_format($price_new_container, 2); ?></span>
                                </div>
                                
                                <div class="qty-controls mt-auto">
                                    <button class="btn btn-outline-secondary btn-qty btn-minus" type="button" data-target="new_round_qty">−</button>
                                    <input type="number" class="form-control qty-input" name="new_round_qty" id="new_round_qty" value="0" min="0" readonly>
                                    <button class="btn btn-outline-secondary btn-qty btn-plus" type="button" data-target="new_round_qty">+</button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card p-4 rounded-4 product-card card-shadow" id="cardNewSlim">
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div>
                                        <h4 class="h5 fw-bold mb-1">New Slim Container</h4>
                                        <p class="text-muted small mb-0">Container + first refill included</p>
                                    </div>
                                    <span class="badge bg-secondary price-badge">₱<?php echo number_format($price_new_container, 2); ?></span>
                                </div>
                                
                                <div class="qty-controls mt-auto">
                                    <button class="btn btn-outline-secondary btn-qty btn-minus" type="button" data-target="new_slim_qty">−</button>
                                    <input type="number" class="form-control qty-input" name="new_slim_qty" id="new_slim_qty" value="0" min="0" readonly>
                                    <button class="btn btn-outline-secondary btn-qty btn-plus" type="button" data-target="new_slim_qty">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Order Summary Column -->
        <div class="col-lg-4">
            <div class="card p-4 rounded-4 summary-card">
                <div class="summary-header">
                    <h3 class="h5 fw-bold mb-0">Order Summary</h3>
                </div>
                
                <div id="summary-items" class="mb-3">
                    <p class="text-muted text-center py-4">No items selected</p>
                </div>

                <div class="summary-total">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="h5 fw-bold mb-0">Total</span>
                        <span id="grandTotal" class="h4 fw-bold text-primary mb-0">₱0.00</span>
                    </div>
                </div>

                <button type="submit" form="orderForm" id="placeOrderBtn" class="btn btn-place-order w-100 rounded-pill mt-3" disabled>
                    Select Items to Continue
                </button>
                
                <p class="text-muted small text-center mt-3 mb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-truck" viewBox="0 0 16 16">
                        <path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5zm1.294 7.456A2 2 0 0 1 4.732 11h5.536a2 2 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456M12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12zm-9 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m9 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/>
                    </svg>
                    Delivery within 1-2 hours
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-primary text-white py-4 mt-auto">
    <div class="container text-center">
        <p class="mb-0">&copy; 2024 Moya - Mineral Water Delivery. All rights reserved. | Rosario, La Union</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const PRICES = {
        refill_round_qty: <?php echo $price_refill; ?>,
        refill_slim_qty: <?php echo $price_refill; ?>,
        new_round_qty: <?php echo $price_new_container; ?>,
        new_slim_qty: <?php echo $price_new_container; ?>
    };
    
    const PRODUCT_NAMES = {
        refill_round_qty: "Standard Round Refill",
        refill_slim_qty: "Slim Container Refill",
        new_round_qty: "New Standard Round",
        new_slim_qty: "New Slim Container"
    };

    const cards = {
        refill_round_qty: document.getElementById('cardRefillRound'),
        refill_slim_qty: document.getElementById('cardRefillSlim'),
        new_round_qty: document.getElementById('cardNewRound'),
        new_slim_qty: document.getElementById('cardNewSlim')
    };

    const placeOrderBtn = document.getElementById('placeOrderBtn');

    function updateCalculation() {
        let grandTotal = 0;
        let totalItems = 0;
        let summaryHtml = '';

        for (const qtyId in PRICES) {
            const input = document.getElementById(qtyId);
            const quantity = parseInt(input.value) || 0;
            
            if (quantity > 0) {
                const price = PRICES[qtyId];
                const subtotal = quantity * price;
                grandTotal += subtotal;
                totalItems += quantity;

                summaryHtml += `
                    <div class="summary-item d-flex justify-content-between">
                        <span class="text-muted">${PRODUCT_NAMES[qtyId]} <span class="fw-semibold">×${quantity}</span></span>
                        <span class="fw-semibold">₱${subtotal.toFixed(2)}</span>
                    </div>`;

                const cardType = qtyId.includes('refill') ? 'active-primary' : 'active-secondary';
                cards[qtyId].classList.add(cardType);
            } else {
                cards[qtyId].classList.remove('active-primary', 'active-secondary');
            }
        }

        const summaryContainer = document.getElementById('summary-items');
        if (summaryHtml) {
            summaryContainer.innerHTML = summaryHtml;
        } else {
            summaryContainer.innerHTML = '<p class="text-muted text-center py-4 mb-0">No items selected</p>';
        }
        
        document.getElementById('grandTotal').textContent = `₱${grandTotal.toFixed(2)}`;

        if (totalItems > 0) {
            placeOrderBtn.disabled = false;
            placeOrderBtn.textContent = `Place Order (${totalItems} item${totalItems > 1 ? 's' : ''}) - ₱${grandTotal.toFixed(2)}`;
        } else {
            placeOrderBtn.disabled = true;
            placeOrderBtn.textContent = 'Select Items to Continue';
        }
    }

    document.querySelectorAll('.btn-plus, .btn-minus').forEach(button => {
        button.addEventListener('click', function() {
            const targetInputId = this.getAttribute('data-target');
            const input = document.getElementById(targetInputId);
            
            if (!input) return;

            let qty = parseInt(input.value) || 0;

            if (this.classList.contains('btn-plus')) {
                qty++;
            } else if (qty > 0) {
                qty--;
            }
            
            input.value = qty;
            updateCalculation();
        });
    });
    
    updateCalculation();
</script>
</body>
</html>