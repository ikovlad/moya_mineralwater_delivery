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
                        <span class="fw-bold">Please drag the green pin to your exact house location.</span> 
                        The <span class="text-primary fw-bold">blue pins</span> are our reference delivery points.
                    </p>
                    <div id="location-check-map" class="mb-3"></div>
                    <div id="addressDisplay" class="text-muted">Drag the marker to get the address...</div>
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
        let geocoder = L.Control.Geocoder.nominatim();
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
                    reverseGeocode(position);
                });

            } else {
                 modalMap.invalidateSize(); 
            }
            
            const initialPosition = userMarker.getLatLng();
            validateMarkerLocation(initialPosition);
            reverseGeocode(initialPosition);
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

        function reverseGeocode(latlng) {
            const addressDiv = document.getElementById('addressDisplay');
            addressDiv.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Finding address...';

            geocoder.reverse(latlng, modalMap.options.crs.scale(modalMap.getZoom()), function(results) {
                if (results && results.length > 0 && results[0].name) {
                     addressDiv.textContent = results[0].name;
                } else {
                    addressDiv.textContent = 'Could not find address for this location.';
                 }
            }, this);
        }

    </script>
</body>
</html>