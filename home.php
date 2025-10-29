<?php
session_start();
require_once "config.php";

// Disable caching so "Back" won't show logged-in pages after logout
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Redirect if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.html");
    exit;
}

$user_name = htmlspecialchars($_SESSION["full_name"]);
// --- GET USER'S BARANGAY FROM SESSION ---
$user_barangay = htmlspecialchars($_SESSION["address_barangay"]);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome, <?php echo $user_name; ?> | Moya Water Delivery</title>

    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <!-- Bricolage Grotesque & Lato Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />

    <style>
        :root {
            --moya-primary: #008080;
            --moya-secondary: #00bfff;
            --moya-cta: #ff9900;
            --moya-bg: #f5fcfc;
            --bs-primary: var(--moya-primary);
            --bs-primary-rgb: 0, 128, 128;
        }
        body {
            font-family: Lato, sans-serif;
            background-image: url(img/bg.svg);
            color: #1f2937;
            font-size: 1.5em;
        }
        h2 {
            font-family: Bricolage Grotesque, sans-serif;
            font-size: 5rem;
        }
        .hero-bg {
            background-image: url(img/bg.svg);
        }
        .hero-bg h1 {
            font-family: Bricolage Grotesque, sans-serif;
            font-size: 5rem;
        }
        .hero-bg .lead {
            font-size: 1.5rem;
            font-family: Lato, sans-serif;
            font-weight: 350;
        }
        #hero .col-md-5 {
            display: flex;
            align-items: flex-end;
            position: relative;
            min-height: 500px;
        }
        #hero .col-md-5 img {
            width: 100%;
            height: auto;
            object-fit: contain;
            transform: scale(1.3);
            transform-origin: bottom center;
            position: absolute;
            bottom: 0;
        }
        .btn-cta {
            background-color: #007bff;
            border-color: #007bff;
            color: #fff !important;
            font-weight: 700;
            padding: .75rem 2rem;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
        }
        .btn-cta:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            color: #fff !important;
            box-shadow: 0 10px 20px rgba(0, 123, 255, 0.5) !important;
            transform: translateY(-2px);
        }
        .card-shadow {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 10px 15px rgba(0, 0, 0, 0.03);
        }
        .product-img {
            max-width: 100%;
            height: 250px;
            object-fit: contain;
        }
        .hover-border-primary:hover {
            border-color: var(--moya-primary) !important;
        }
        #profileDropdown {
            background-color: #008080 !important;
        }
        #location-check-map {
            height: 450px;
            width: 100%;
            border-radius: 0.5rem;
            cursor: grab;
        }
        .leaflet-marker-draggable {
             cursor: grabbing !important;
        }
        #addressDisplay {
            min-height: 40px;
            font-size: 0.9rem;
            background-color: #e9ecef;
            padding: 8px 12px;
            border-radius: 4px;
            margin-top: 10px;
        }
        #products h2 {
            font-family: Bricolage Grotesque, sans-serif;
            font-size: 4rem;
            color: var(--moya-primary) !important;
        }
        #process h2 {
            font-family: Bricolage Grotesque, sans-serif;
            font-size: 4rem;
            color: var(--moya-primary) !important;
        }
        #process .h5 {
            font-size: 1.2rem;
        }
        #process p {
            font-size: 1.2rem;
        }

          /* --- MAP --- */
        #location .lead {
            font-weight: 400;
        }
        .map-placeholder {
            min-height: 350px;
            background-color: #e3f2fd;
            border: 2px solid #90caf9;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            border-radius: 0.75rem;
        }

            /* --- How to Order Section Styles --- */
    #how-to-order {
        background: linear-gradient(135deg, #e6f7f7 0%, #f0fbfb 100%);
        position: relative;
        overflow: hidden;
    }

    #how-to-order::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(0, 128, 128, 0.03) 1px, transparent 1px);
        background-size: 30px 30px;
        z-index: 0;
    }

    #how-to-order .container {
        position: relative;
        z-index: 1;
    }

    #how-to-order h2 {
        font-family: 'Bricolage Grotesque', sans-serif;
        font-size: 4rem;
        font-weight: bold;
        color: var(--moya-primary) !important;
        margin-bottom: 1rem;
    }

    #how-to-order .section-subtitle {
        font-size: 1.25rem;
        color: #555;
        max-width: 900px;
        margin: 0 auto 3rem;
        white-space: nowrap;
    }
    
    @media (max-width: 992px) {
        #how-to-order .section-subtitle {
            white-space: normal;
        }
    }

    .process-timeline {
        position: relative;
        padding: 2rem 0;
    }

    .timeline-step {
        display: flex;
        align-items: flex-start;
        margin-bottom: 3rem;
        position: relative;
        animation: fadeInUp 0.6s ease-out forwards;
        opacity: 0;
    }

    .timeline-step:nth-child(1) { animation-delay: 0.1s; }
    .timeline-step:nth-child(2) { animation-delay: 0.2s; }
    .timeline-step:nth-child(3) { animation-delay: 0.3s; }
    .timeline-step:nth-child(4) { animation-delay: 0.4s; }
    .timeline-step:nth-child(5) { animation-delay: 0.5s; }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .step-number {
        flex-shrink: 0;
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--moya-primary) 0%, #006666 100%);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 700;
        box-shadow: 0 6px 20px rgba(0, 128, 128, 0.3);
        position: relative;
        z-index: 2;
        transition: all 0.3s ease;
    }

    .timeline-step:hover .step-number {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 8px 25px rgba(0, 128, 128, 0.4);
    }

    .step-connector {
        position: absolute;
        left: 39px;
        top: 80px;
        width: 3px;
        height: calc(100% - 50px);
        background: linear-gradient(to bottom, var(--moya-primary), transparent);
        z-index: 1;
    }

    .timeline-step:last-child .step-connector {
        display: none;
    }

    .step-content {
        flex: 1;
        margin-left: 2rem;
        background: white;
        padding: 1.5rem 2rem;
        border-radius: 1rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border-left: 4px solid var(--moya-primary);
    }

    .timeline-step:hover .step-content {
        transform: translateX(10px);
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.12);
    }

    .step-header {
        display: flex;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .step-icon-badge {
        width: 40px;
        height: 40px;
        background: rgba(0, 128, 128, 0.1);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        color: var(--moya-primary);
        font-size: 1.3rem;
    }

    .step-content h5 {
        font-weight: 700;
        color: var(--moya-primary);
        margin: 0;
        font-size: 1.4rem;
    }

    .step-content p {
        color: #555;
        margin: 0;
        font-size: 1.05rem;
        line-height: 1.6;
    }

    .step-tag {
        display: inline-block;
        background: rgba(0, 128, 128, 0.1);
        color: var(--moya-primary);
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-top: 0.5rem;
    }

        /* Responsive adjustments for arrows */
    @media (max-width: 768px) {
        #how-to-order h2 {
            font-size: 2.5rem;
        }

        .step-number {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
        }

        .step-connector {
            left: 29px;
            top: 60px;
        }

        .step-content {
            margin-left: 1rem;
            padding: 1.25rem 1.5rem;
        }

        .step-content h5 {
            font-size: 1.2rem;
        }

        .step-content p {
            font-size: 0.95rem;
        }
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container py-2">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center fs-3 gap-2" href="#">
                <img src="img/moya_logo.png" alt="moya_logo" style="height: 50px; width: auto; object-fit: contain;">
                Moya
            </a>
            <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 fw-semibold fs-5">
                    <li class="nav-item"><a class="nav-link" href="#hero">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#products">Containers</a></li>
                    <li class="nav-item"><a class="nav-link" href="#process">Delivery</a></li>
                    <li class="nav-item"><a class="nav-link" href="#location">Area</a></li>
                    <li class="nav-item dropdown ms-lg-3">
                        <a class="nav-link dropdown-toggle btn btn-primary rounded-pill px-4 btn-md text-white" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $user_name; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="profileDropdown">
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

    <section id="hero" class="hero-bg py-5 py-xl-10">
        <div class="container">
            <div class="row align-items-center gx-5">
                <div class="col-md-7 order-md-1 order-2 text-center text-md-start pt-5">
                    <h1 class="display-4 fw-bolder lh-1 mb-3 text-gray-800">
                        Welcome back, <br><span class="text-primary d-block d-sm-inline"><?php echo $user_name; ?>!</span>
                    </h1>
                    <p class="lead text-secondary mb-4 mx-auto mx-md-0 text-black" style="max-width: 600px;">
                        Ready to order? Enjoy fast delivery of premium mineral water in <b>Rosario, La Union</b> — straight to your door in <b>1–2 hours</b>.
                    </p>
                    <div class="d-grid d-sm-flex gap-3 justify-content-center justify-content-md-start">
                        <a href="order.php" class="btn btn-cta btn-lg rounded-pill shadow-lg">Place an Order</a>
                        <a href="#products" class="btn btn-outline-primary btn-lg rounded-pill fw-bold d-inline-flex align-items-center justify-content-center" style="border-width: 2px; text-decoration: none; background-color: var(--moya-bg);">View Containers</a>
                    </div>
                </div>
                <div class="col-md-5 order-md-2 order-1 text-center d-flex align-items-center justify-content-center" style="min-height: 500px;">
                    <img src="img/front_img.png" class="img-fluid w-100" alt="Moya Delivery" style="max-height: 600px; object-fit: contain;">
                </div>
            </div>
        </div>
    </section>

    <section id="products" class="py-5 py-xl-10 bg-white">
        <div class="container">
            <h2 class="display-6 fw-bold text-center mb-2">Our Gallon Options</h2>
            <p class="text-center text-secondary mb-5 mx-auto text-black" style="max-width: 700px;">
                Choose your preferred container and order directly — no need to log in again.
            </p>
            <div class="row g-4 justify-content-center">
                <div class="col-md-5">
                    <div class="card p-4 rounded-4 card-shadow text-center h-100 border-2 border-transparent transition duration-300 hover-border-primary">
                        <img src="img/round-water-jug.png" class="mx-auto mb-3 product-img" alt="Round Jug">
                        <h3 class="h4 fw-semibold mb-2 text-gray-800">Standard Round Container</h3>
                        <p class="text-muted mb-3">Traditional and robust 5-gallon container.</p>
                        <div class="bg-light rounded-3 p-3 mb-3">
                            <p class="mb-1 fw-bold text-primary">REFILL PRICE:</p>
                            <p class="display-6 fw-bold text-primary mb-0">₱20.00</p>
                        </div>
                        <div class="mt-3">
                            <p class="fw-semibold text-secondary mb-1">Buy New Container Option:</p>
                            <p class="h5 fw-bold text-dark">₱120.00 (Includes container + First Refill)</p>
                        </div>
                        <a href="order.php?item=round" class="btn btn-primary btn-lg rounded-pill fw-semibold shadow-sm w-100 mt-4">Order Now</a>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card p-4 rounded-4 card-shadow text-center h-100 border-2 border-transparent transition duration-300 hover-border-primary">
                        <img src="img/slim-water-gallon.jpg" class="mx-auto mb-3 product-img" alt="Slim Jug">
                        <h3 class="h4 fw-semibold mb-2 text-gray-800">Slim Container with Faucet</h3>
                        <p class="text-muted mb-3">Space-saving and easy to use.</p>
                        <div class="bg-light rounded-3 p-3 mb-3">
                            <p class="mb-1 fw-bold text-primary">REFILL PRICE:</p>
                            <p class="display-6 fw-bold text-primary mb-0">₱20.00</p>
                        </div>
                        <div class="mt-3">
                            <p class="fw-semibold text-secondary mb-1">Buy New Container Option:</p>
                            <p class="h5 fw-bold text-dark">₱120.00 (Includes container + First Refill)</p>
                        </div>
                        <a href="order.php?item=slim" class="btn btn-primary btn-lg rounded-pill fw-semibold shadow-sm w-100 mt-4">Order Now</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===============================
         NEW: How to Order Section
         =============================== -->
    <section id="how-to-order" class="py-5 py-xl-10">
        <div class="container">
            <h2 class="text-center mb-2">How to Order & Process</h2>
            <p class="text-center section-subtitle">
                Follow these simple steps to get your premium water delivered fresh to your door
            </p>

            <div class="process-timeline">
                <!-- Step 1 -->
                <div class="timeline-step">
                    <div class="step-number">1</div>
                    <div class="step-connector"></div>
                    <div class="step-content">
                        <div class="step-header">
                            <div class="step-icon-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                                    <path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5"/>
                                </svg>
                            </div>
                            <h5>Create Account & Place Order</h5>
                        </div>
                        <p>Sign up or log in to your account. Browse our container options, select your products, and submit your order. Make sure to review and agree to our order policy before completing your request.</p>
                        <span class="step-tag">Your Action Required</span>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="timeline-step">
                    <div class="step-number">2</div>
                    <div class="step-connector"></div>
                    <div class="step-content">
                        <div class="step-header">
                            <div class="step-icon-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.68.68 0 0 0 .178.643l2.457 2.457a.68.68 0 0 0 .644.178l2.189-.547a1.75 1.75 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.6 18.6 0 0 1-7.01-4.42 18.6 18.6 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877zm10.761.135a.5.5 0 0 1 .708 0l2.5 2.5a.5.5 0 0 1 0 .708l-2.5 2.5a.5.5 0 0 1-.708-.708L14.293 4H9.5a.5.5 0 0 1 0-1h4.793l-1.647-1.646a.5.5 0 0 1 0-.708"/>
                                </svg>
                            </div>
                            <h5>Receive Confirmation Call</h5>
                        </div>
                        <p>Your order will appear as "Pending" in your profile. Our team will call you shortly to confirm your order details, delivery address, and preferred time. Once confirmed, the status updates to "Confirmed".</p>
                        <span class="step-tag">We'll Contact You</span>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="timeline-step">
                    <div class="step-number">3</div>
                    <div class="step-connector"></div>
                    <div class="step-content">
                        <div class="step-header">
                            <div class="step-icon-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5"/>
                                    <path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0z"/>
                                </svg>
                            </div>
                            <h5>Container Pickup</h5>
                        </div>
                        <p>Our delivery rider will arrive at your location to collect your empty container(s) for refilling. After the pickup is complete, please go to your profile and update the order status to "Confirm Pickup".</p>
                        <span class="step-tag">Your Action Required</span>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="timeline-step">
                    <div class="step-number">4</div>
                    <div class="step-connector"></div>
                    <div class="step-content">
                        <div class="step-header">
                            <div class="step-icon-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5zm1.294 7.456A2 2 0 0 1 4.732 11h5.536a2 2 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456M12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12z"/>
                                    <path d="M10 11.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m-7 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/>
                                </svg>
                            </div>
                            <h5>Refilling & Out for Delivery</h5>
                        </div>
                        <p>We'll refill your container with premium purified water at our station. Once your order is ready and our rider is heading to your location, we'll update the status to "Set Out for Delivery".</p>
                        <span class="step-tag">We're Processing</span>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="timeline-step">
                    <div class="step-number">5</div>
                    <div class="step-content">
                        <div class="step-header">
                            <div class="step-icon-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4"/>
                                    <path d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2z"/>
                                </svg>
                            </div>
                            <h5>Delivery & Payment</h5>
                        </div>
                        <p>The rider delivers your freshly refilled container to your doorstep. Pay using Cash on Delivery (COD). After receiving your order and making payment, please update the status to "Confirm Delivery & Payment" in your profile.</p>
                        <span class="step-tag">Your Action Required</span>
                    </div>
                </div>
            </div>

            <!-- Optional: Add a helpful note -->
            <div class="text-center mt-5">
                <div class="alert alert-info d-inline-block" style="max-width: 600px; border-left: 4px solid var(--moya-primary);">
                    <strong>💡 Pro Tip:</strong> Keep your profile updated throughout the process for accurate tracking and faster service!
                </div>
            </div>
        </div>
    </section>

    <section id="promo-banner" class="text-center py-3" style="background: linear-gradient(to right, #4fc3f7, #29b6f6); color: white; letter-spacing: 0.5px; font-size: 1.2rem; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);">
        <div class="container">
            <h4 class="m-0 fw-bold">
                💧 Buy Five, Get One Refill Free! — Stay hydrated and save more! 💧
            </h4>
        </div>
    </section>

    <section id="process" class="py-5 py-xl-10" style="background-color: #e9f2ff;">
        <div class="container">
            <h2 class="display-6 fw-bold text-center mb-5">Delivery & Schedule</h2>
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 card-shadow h-100">
                        <svg class="mb-3 text-primary" width="48" height="48" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 15h2v2h-2zm0-8h2v6h-2z" /></svg>
                        <h3 class="h5 fw-bold text-gray-800">Operating Hours</h3>
                        <p class="text-secondary mb-0">
                            <strong>8:00 AM - 4:00 PM</strong> daily. <br>
                            <small>Last orders accepted at 4:00 PM.</small>
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 card-shadow h-100">
                        <svg class="mb-3 text-primary" width="48" height="48" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.22.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm11 0c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z" /></svg>
                        <h3 class="h5 fw-bold text-gray-800">Fast Delivery</h3>
                        <p class="text-secondary mb-0">
                            Delivery is <strong>within the day</strong>, typically arriving <strong>1-2 hours</strong> after your order is confirmed. <br>
                            <small>Last delivery run ends at 5:00 PM.</small>
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 card-shadow h-100">
                        <svg class="mb-3 text-success" width="48" height="48" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M21 6H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 10H3V8h18v8zm-5-3c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z" /></svg>
                        <h3 class="h5 fw-bold text-gray-800">Payment Method</h3>
                        <p class="text-secondary mb-0">
                            We accept <strong>Cash on Delivery (COD)</strong> only. Please prepare the exact amount for a smooth transaction.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="location" class="py-5 py-xl-10">
        <div class="container">
            <div class="row align-items-center gx-5">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="display-6 fw-bold mb-3">Service Area: Rosario, La Union</h2>
                    <p class="lead text-secondary mb-4">
                        We proudly serve <b>Barangay Cataguingtingan</b> and surrounding <b>In-Town areas</b> only. Use the button below to pinpoint your location and check if we can deliver to you.
                    </p>
                    <button onclick="openLocationCheckModal()" class="btn btn-primary btn-lg rounded-pill fw-semibold">
                        Confirm My Delivery Address
                    </button>
                </div>
                <div class="col-lg-6 h-100">
                    <div id="main-page-map" class="map-placeholder rounded-4 card-shadow" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </section>
    
    <div class="modal fade" id="locationCheckModal" tabindex="-1" aria-labelledby="locationCheckModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="locationCheckModalLabel">Pinpoint Your Delivery Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">
                        We've placed a <span class="text-success fw-bold">green pin</span> near your registered barangay (<b><?php echo $user_barangay; ?></b>). 
                        <span class="fw-bold">Please drag the green pin to your exact house location, to know if we delivering at your place.</span> 
                        The <span class="text-primary fw-bold">blue pins</span> are our reference delivery points.
                    </p>
                    <div id="location-check-map" class="mb-3"></div>
                    <div id="locationStatus" class="mt-3 text-center fw-bold"></div>
                </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                     </div>
            </div>
        </div>
    </div>

    <footer class="bg-primary text-white py-4 mt-auto">
        <div class="container text-center">
            <p class="mb-0" style="font-size: 1rem;">&copy; 2024 Moya - Mineral Water Delivery. All rights reserved. | Rosario, La Union.</p>
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    const serviceAreaPoints = [
        [16.219468, 120.493981], [16.213475, 120.501831], [16.225049, 120.503922],
        [16.246571, 120.487866], [16.239948, 120.468656], [16.228787, 120.459740],
        [16.238981, 120.455706], [16.248206, 120.453848], [16.255083, 120.469280],
        [16.265017, 120.445564], [16.224417, 120.450107], [16.220696, 120.468793],
        [16.214975, 120.468423], [16.214365, 120.491307], [16.211052, 120.484091],
        [16.236634, 120.431094], [16.234697, 120.421645], [16.232188, 120.407840],
        [16.220671, 120.412298], [16.242430, 120.404603], [16.250993, 120.429892],
        [16.266720, 120.408259], [16.281248, 120.443265], [16.268898, 120.475935],
        [16.249906, 120.495360], [16.277226, 120.489522], [16.281798, 120.488149],
        [16.214498, 120.429782]
    ];

    const barangayCoordinateLookup = {
        "Cataguingtingan": [16.239948, 120.468656],
        "Poblacion East": [16.2143, 120.4913],
        "Poblacion West": [16.2134, 120.5018],
        "Subusub": [16.2287, 120.4597],
        "Bani": [16.2465, 120.4878],
    };
    
    const USER_BARANGAY_NAME = <?php echo json_encode($user_barangay); ?>;
    let modalMap;
    let userMarker;
    // DELETED: let geocoder = L.Control.Geocoder.nominatim();
    const DELIVERY_RADIUS_METERS = 2000;

    document.addEventListener('DOMContentLoaded', function() {
        const mainMap = L.map('main-page-map').setView([16.245, 120.47], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(mainMap);

        serviceAreaPoints.forEach(coords => {
            L.marker(coords).addTo(mainMap).bindPopup('We deliver near this point.');
        });
    });

    const locationModalElement = document.getElementById('locationCheckModal');
    const locationModal = new bootstrap.Modal(locationModalElement);
    
    function openLocationCheckModal() {
        locationModal.show();
    }
    
    locationModalElement.addEventListener('shown.bs.modal', function() {
        if (!modalMap) {
            let initialCoords = barangayCoordinateLookup[USER_BARANGAY_NAME] || [16.245, 120.47];
            
            modalMap = L.map('location-check-map').setView(initialCoords, 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(modalMap);
            
            serviceAreaPoints.forEach(coords => {
                L.marker(coords).addTo(modalMap);
            });

            userMarker = L.marker(initialCoords, {
                draggable: true,
                icon: L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                    iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
                })
            }).addTo(modalMap);

            userMarker.on('dragend', function(event) {
                const marker = event.target;
                const position = marker.getLatLng();
                console.log('Marker dragged to:', position);
                validateMarkerLocation(position);
                // DELETED: reverseGeocode(position);
            });

        } else {
             modalMap.invalidateSize(); 
        }
        
        const initialPosition = userMarker.getLatLng();
        validateMarkerLocation(initialPosition);
        // DELETED: reverseGeocode(initialPosition);
        userMarker.bindPopup('Drag this pin to your exact house location.').openPopup();

    });

    function validateMarkerLocation(userLatLng) {
        const statusDiv = document.getElementById('locationStatus');
        statusDiv.className = 'mt-3 text-center fw-bold';
        
        let isInside = false;
        for (const point of serviceAreaPoints) {
            const servicePointLatLng = L.latLng(point[0], point[1]);
            const distance = userLatLng.distanceTo(servicePointLatLng);

            console.log(`Distance to point ${point}: ${distance} meters`);

            if (distance <= DELIVERY_RADIUS_METERS) {
                isInside = true;
                break;
            }
        }

        if (isInside) {
            statusDiv.innerHTML = '<span class="text-success">✅ Great! Your selected location is within our delivery area.</span>';
        } else {
            statusDiv.innerHTML = '<span class="text-danger">❌ Sorry, your selected location appears to be outside our service radius.</span>';
        }
    }

    // DELETED: function reverseGeocode(latlng) { ... }

</script>
</body>
</html>