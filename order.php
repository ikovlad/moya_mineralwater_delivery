<?php
// Initialize the session and include config
session_start();
require_once "config.php"; // Assuming config.php is in the same directory

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
// This creates a safe string for the SQL IN clause: 'Name1','Name2',...
$product_names_str = "'" . implode("','", array_map([$conn, 'real_escape_string'], $product_names)) . "'";

// Fetch the prices and IDs for all four specific products
$sql_prices = "SELECT id, name, price FROM products WHERE name IN ($product_names_str)";
$result_prices = mysqli_query($conn, $sql_prices);

if ($result_prices) {
    while ($row = mysqli_fetch_assoc($result_prices)) {
        // Store product ID and price using the full name as the key
        $product_data[$row['name']] = [
            'id' => $row['id'],
            'price' => (float)$row['price']
        ];
    }
    mysqli_free_result($result_prices);
}

// Assign fetched prices and IDs with fallback default values
$price_refill = $product_data['Standard Round Refill']['price'] ?? 40.00;
$price_new_container = $product_data['New Standard Round']['price'] ?? 250.00;

$id_refill_round = $product_data['Standard Round Refill']['id'] ?? 0;
$id_refill_slim = $product_data['Slim Container Refill']['id'] ?? 0;
$id_new_round = $product_data['New Standard Round']['id'] ?? 0;
$id_new_slim = $product_data['New Slim Container']['id'] ?? 0;

// Close the connection as we are done with DB operations on this page
mysqli_close($conn);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moya - Place Your Order</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { 
            --moya-primary: #008080; /* Teal */
            --moya-secondary: #00bfff; /* Sky Blue */
            --moya-light: #f5fcfc;
            --moya-dark: #333;
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--moya-light); 
        }
        .bg-primary { background-color: var(--moya-primary) !important; }
        .text-primary { color: var(--moya-primary) !important; }
        .bg-secondary { background-color: var(--moya-secondary) !important; }
        .text-secondary { color: var(--moya-secondary) !important; }
        .btn-primary { 
            background-color: var(--moya-primary); 
            border-color: var(--moya-primary);
        }
        .btn-primary:hover {
            background-color: #006666; /* Darker teal */
            border-color: #006666;
        }
        .btn-outline-primary {
            color: var(--moya-primary);
            border-color: var(--moya-primary);
        }
        .btn-outline-primary:hover {
            background-color: var(--moya-primary);
            color: #fff;
        }
        .btn-outline-secondary {
            color: var(--moya-secondary);
            border-color: var(--moya-secondary);
        }
        .btn-outline-secondary:hover {
            background-color: var(--moya-secondary);
            color: #fff;
        }
        .card-shadow { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.07); }
        .product-card { 
            transition: all 0.3s ease; 
            border: 2px solid transparent;
            background-color: #fff;
        }
        .product-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1); 
        }
        .product-card.active-primary {
            border-color: var(--moya-primary);
            box-shadow: 0 6px 15px rgba(0, 128, 128, 0.2);
        }
        .product-card.active-secondary {
            border-color: var(--moya-secondary);
            box-shadow: 0 6px 15px rgba(0, 191, 255, 0.2);
        }
        .qty-input { 
            width: 70px; 
            text-align: center; 
            border-color: #ced4da;
        }
        .qty-input:focus {
            box-shadow: none;
            border-color: var(--moya-primary);
        }
        .welcome-text {
            font-size: 1.1rem;
            color: #555;
        }
        .summary-card {
            background-color: #fff;
            border-top: 4px solid var(--moya-primary);
        }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h1 text-primary fw-bolder">Order Mineral Water</h1>
                <div class="d-flex align-items-center">
                    <p class="mb-0 me-3 welcome-text">Welcome, <strong><?php echo $user_name; ?></strong>!</p>
                    <a href="profile.php" class="btn btn-outline-primary me-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16" class="me-1">
                            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                            <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.52 10.515 4.983 10 8 10s4.48 0.515 5.468 1.37A7 7 0 0 0 8 1"/>
                        </svg>
                        Profile
                    </a>
                    <a href="home.php" class="btn btn-primary">Home</a>
                </div>
            </div>

            <p class="lead text-muted mb-4">You are ordering for delivery to <strong><?php echo $user_barangay; ?></strong>. Select your quantities below.</p>

            <form id="orderForm" action="place_order.php" method="POST">
                
                <!-- These hidden inputs are CRITICAL. They pass the product IDs to the next script. -->
                <input type="hidden" name="product_ids[<?php echo $id_refill_round; ?>]" value="refill_round_qty">
                <input type="hidden" name="product_ids[<?php echo $id_refill_slim; ?>]" value="refill_slim_qty">
                <input type="hidden" name="product_ids[<?php echo $id_new_round; ?>]" value="new_round_qty">
                <input type="hidden" name="product_ids[<?php echo $id_new_slim; ?>]" value="new_slim_qty">

                <h3 class="text-primary fw-bold mb-3">Water Refills</h3>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card p-4 rounded-4 product-card card-shadow" id="cardRefillRound">
                            <div class="d-flex align-items-center">
                                <h2 class="h4 fw-bold mb-0 me-auto">Standard Round Refill</h2>
                                <span class="badge bg-primary fs-5 fw-bold">₱<?php echo number_format($price_refill, 2); ?></span>
                            </div>
                            <p class="text-muted mb-3">For your existing Standard Round 5-gallon container.</p>
                            
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="input-group input-group-sm w-auto">
                                    <button class="btn btn-outline-primary btn-minus" type="button" data-target="refill_round_qty">-</button>
                                    <input type="number" class="form-control qty-input" name="refill_round_qty" id="refill_round_qty" value="0" min="0" readonly>
                                    <button class="btn btn-outline-primary btn-plus" type="button" data-target="refill_round_qty">+</button>
                                </div>
                                <span class="text-primary fw-semibold">Quantity</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card p-4 rounded-4 product-card card-shadow" id="cardRefillSlim">
                            <div class="d-flex align-items-center">
                                <h2 class="h4 fw-bold mb-0 me-auto">Slim Container Refill</h2>
                                <span class="badge bg-primary fs-5 fw-bold">₱<?php echo number_format($price_refill, 2); ?></span>
                            </div>
                            <p class="text-muted mb-3">For your existing Slim Container with Faucet.</p>
                            
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="input-group input-group-sm w-auto">
                                    <button class="btn btn-outline-primary btn-minus" type="button" data-target="refill_slim_qty">-</button>
                                    <input type="number" class="form-control qty-input" name="refill_slim_qty" id="refill_slim_qty" value="0" min="0" readonly>
                                    <button class="btn btn-outline-primary btn-plus" type="button" data-target="refill_slim_qty">+</button>
                                </div>
                                <span class="text-primary fw-semibold">Quantity</span>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="text-primary fw-bold mt-3 mb-3">New Containers</h3>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card p-4 rounded-4 product-card card-shadow" id="cardNewRound">
                            <div class="d-flex align-items-center">
                                <h2 class="h4 fw-bold mb-0 me-auto">New Standard Round</h2>
                                <span class="badge bg-secondary fs-5 fw-bold">₱<?php echo number_format($price_new_container, 2); ?></span>
                            </div>
                            <p class="text-muted mb-3">A new 5-gallon Round container, with its first water refill.</p>
                            
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="input-group input-group-sm w-auto">
                                    <button class="btn btn-outline-secondary btn-minus" type="button" data-target="new_round_qty">-</button>
                                    <input type="number" class="form-control qty-input" name="new_round_qty" id="new_round_qty" value="0" min="0" readonly>
                                    <button class="btn btn-outline-secondary btn-plus" type="button" data-target="new_round_qty">+</button>
                                </div>
                                <span class="text-secondary fw-semibold">Quantity</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card p-4 rounded-4 product-card card-shadow" id="cardNewSlim">
                            <div class="d-flex align-items-center">
                                <h2 class="h4 fw-bold mb-0 me-auto">New Slim Container</h2>
                                <span class="badge bg-secondary fs-5 fw-bold">₱<?php echo number_format($price_new_container, 2); ?></span>
                            </div>
                            <p class="text-muted mb-3">A new Slim Container with Faucet, with its first water refill.</p>
                            
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="input-group input-group-sm w-auto">
                                    <button class="btn btn-outline-secondary btn-minus" type="button" data-target="new_slim_qty">-</button>
                                    <input type="number" class="form-control qty-input" name="new_slim_qty" id="new_slim_qty" value="0" min="0" readonly>
                                    <button class="btn btn-outline-secondary btn-plus" type="button" data-target="new_slim_qty">+</button>
                                </div>
                                <span class="text-secondary fw-semibold">Quantity</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card p-4 rounded-4 card-shadow border-0 mt-3 summary-card">
                    <h3 class="h5 fw-bold mb-3 border-bottom pb-2">Order Summary</h3>
                    
                    <div id="summary-items"></div>

                    <div class="d-flex justify-content-between mb-3 pt-2 border-top border-2 mt-2">
                        <span class="h4 fw-bold text-primary">GRAND TOTAL:</span>
                        <span id="grandTotal" class="h4 fw-bold text-primary">₱0.00</span>
                    </div>

                    <button type="submit" id="placeOrderBtn" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold" disabled>
                        Place Order
                    </button>
                    
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // --- Pass prices from PHP to JavaScript ---
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
    }

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

        // Loop through each price to calculate totals
        for (const qtyId in PRICES) {
            const input = document.getElementById(qtyId);
            const quantity = parseInt(input.value) || 0;
            
            if (quantity > 0) {
                const price = PRICES[qtyId];
                const subtotal = quantity * price;
                grandTotal += subtotal;
                totalItems += quantity;

                // Add item to summary view
                summaryHtml += `
                    <div class="d-flex justify-content-between mb-1">
                        <span>${PRODUCT_NAMES[qtyId]} (x${quantity})</span>
                        <span class="fw-semibold">₱${subtotal.toFixed(2)}</span>
                    </div>`;

                // Highlight active card
                const cardType = qtyId.includes('refill') ? 'active-primary' : 'active-secondary';
                cards[qtyId].classList.add(cardType);
            } else {
                 // Remove highlight if quantity is zero
                cards[qtyId].classList.remove('active-primary', 'active-secondary');
            }
        }

        // Update the summary and total displays
        document.getElementById('summary-items').innerHTML = summaryHtml;
        document.getElementById('grandTotal').textContent = `₱${grandTotal.toFixed(2)}`;

        // Update Button State
        if (totalItems > 0) {
            placeOrderBtn.disabled = false;
            placeOrderBtn.textContent = `Place Order (${totalItems} items) - ₱${grandTotal.toFixed(2)}`;
        } else {
            placeOrderBtn.disabled = true;
            placeOrderBtn.textContent = `Place Order`;
        }
    }

    // Event listeners for ALL quantity buttons (+ and -)
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
            
            // Recalculate everything after any change
            updateCalculation();
        });
    });
    
    // Initial calculation on page load
    updateCalculation();
</script>
</body>
</html>