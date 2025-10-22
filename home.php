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

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
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
            font-family: 'Inter', sans-serif; 
            background-color: var(--moya-bg);
            color: #1f2937;
        }
        /* ... (keep all your other styles: hero-bg, btn-cta, card-shadow, etc.) ... */
         .hero-bg {
            background-image: linear-gradient(to bottom right, #ffffff, var(--moya-bg));
        }
        .btn-cta {
            background-color: #007bff; /* Primary Blue */
            border-color: #007bff;
            color: #fff !important;
            font-weight: 700;
            padding: .75rem 2rem;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
        }
        .btn-cta:hover {
            background-color: #0056b3; /* Darker blue on hover */
            border-color: #0056b3;
            color: #fff !important; /* Keep text white on hover */
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
            cursor: grab; /* Indicate map is interactive */
        }
        .leaflet-marker-draggable {
             cursor: grabbing !important; /* Indicate marker is being dragged */
        }
        #addressDisplay {
            min-height: 40px; /* Reserve space for address */
            font-size: 0.9rem;
            background-color: #e9ecef;
            padding: 8px 12px;
            border-radius: 4px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container py-2">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="#">
                 <svg class="me-2 text-info" width="28" height="28" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.477 2 12c0 3.924 2.222 7.375 5.508 9.062a1 1 0 001.037-.091l1.545-1.545a1 1 0 00-.091-1.037C7.545 17.022 6 14.73 6 12c0-3.314 2.686-6 6-6s6 2.686 6 6c0 2.73-.545 5.022-2.991 7.429a1 1 0 00-.091 1.037l1.545 1.545a1 1 0 001.037.091C19.778 19.375 22 15.924 22 12 22 6.477 17.523 2 12 2z" /></svg>
                Moya
            </a>
            <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto fw-semibold align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#hero">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#products">Containers</a></li>
                    <li class="nav-item"><a class="nav-link" href="#process">Delivery</a></li>
                    <li class="nav-item"><a class="nav-link" href="#location">Area</a></li>
                    <li class="nav-item dropdown ms-lg-3">
                        <a class="nav-link dropdown-toggle btn btn-primary rounded-pill px-3 text-white" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                <div class="col-md-7 text-center text-md-start pt-5">
                    <h1 class="display-5 fw-bold lh-1 mb-3">
                        Welcome back, <span class="text-primary"><?php echo $user_name; ?>!</span>
                    </h1>
                    <p class="lead text-secondary mb-4" style="max-width: 600px;">
                        Ready to order? Enjoy fast delivery of premium mineral water in <b>Rosario, La Union</b> — straight to your door in 1–2 hours.
                    </p>
                    <div class="d-grid d-sm-flex gap-3 justify-content-center justify-content-md-start">
                        <a href="order.php" class="btn btn-cta btn-lg rounded-pill shadow-lg">Place an Order</a>
                        <a href="#products" class="btn btn-outline-primary btn-lg rounded-pill fw-bold">View Containers</a>
                    </div>
                </div>
                <div class="col-md-5 text-center">
                    <img src="img/delivery.png" class="img-fluid rounded-4 shadow-lg" alt="Moya Delivery Van">
                </div>
            </div>
        </div>
    </section>
    <section id="products" class="py-5 bg-white">
        <div class="container">
            <h2 class="display-6 fw-bold text-center mb-2">Our Gallon Options</h2>
            <p class="text-center text-secondary mb-5 mx-auto" style="max-width: 700px;">
                Choose your preferred container and order directly — no need to log in again.
            </p>
            <div class="row g-4 justify-content-center">
                <div class="col-md-5">
                    <div class="card p-4 rounded-4 card-shadow text-center h-100 hover-border-primary">
                        <img src="img/round-water-jug.png" class="mx-auto mb-3 product-img" alt="Round Jug">
                        <h3 class="h4 fw-semibold mb-2">Standard Round Container</h3>
                        <p class="text-muted mb-3">Traditional and robust 5-gallon container.</p>
                        <p class="h5 fw-bold text-primary mb-0">₱20.00 per refill</p>
                        <a href="order.php?item=round" class="btn btn-primary rounded-pill fw-semibold shadow-sm w-100 mt-4">Order Now</a>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card p-4 rounded-4 card-shadow text-center h-100 hover-border-primary">
                        <img src="img/slim-water-gallon.jpg" class="mx-auto mb-3 product-img" alt="Slim Jug">
                        <h3 class="h4 fw-semibold mb-2">Slim Container with Faucet</h3>
                        <p class="text-muted mb-3">Space-saving and easy to use.</p>
                        <p class="h5 fw-bold text-primary mb-0">₱20.00 per refill</p>
                        <a href="order.php?item=slim" class="btn btn-primary rounded-pill fw-semibold shadow-sm w-100 mt-4">Order Now</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section id="process" class="py-5 py-xl-10" style="background-color: #e9f2ff;">
        <div class="container">
            <h2 class="display-6 fw-bold text-center mb-5" style="color: var(--moya-primary) !important;">Delivery Promise & Schedule</h2>
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 card-shadow h-100">
                        <svg class="mb-3 text-primary" width="48" height="48" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 15h2v2h-2zm0-8h2v6h-2z" /></svg>
                        <h3 class="h5 fw-bold text-gray-800">Operating Hours</h3>
                        <p class="text-secondary mb-0">
                            <b>8:00 AM - 4:00 PM</b> daily. <br>
                            <b>Last orders accepted at 4:00 PM.</b>
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 card-shadow h-100">
                        <svg class="mb-3 text-cta" width="48" height="48" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M21 13v-2h-3V8h-2v3h-2v-3h-2v3h-2v-3H8v5h2v-3h2v3h2v-3h2v3h3zm-8-5h2V6h-2v2zm-4 0h2V6H9v2zm8 0h2V6h-2v2z" /><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" /></svg>
                        <h3 class="h5 fw-bold text-gray-800">Guaranteed Fast Delivery</h3>
                        <p class="text-secondary mb-0">
                            Delivery is <b>within the day</b>, typically arriving <b>1-2 hours</b> after confirmation.<br>
                            <b>Last delivery run ends at 5:00 PM.</b>
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 card-shadow h-100">
                         <svg class="mb-3 text-success" width="48" height="48" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M21 6H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 10H3V8h18v8zm-5-3c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z" /></svg>
                        <h3 class="h5 fw-bold text-gray-800">Payment Method</h3>
                        <p class="text-secondary mb-0">
                            We accept <b>Cash on Delivery (COD)</b> only. Please prepare the exact amount.
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
                    <button onclick="openLocationCheckModal()" class="btn btn-primary rounded-pill fw-semibold">
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


    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // --- GLOBAL VARIABLES ---
        
        // Your 28 delivery points (BLUE pins). Keep this list.
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

        // --- BARANGAY COORDINATE LOOKUP TABLE ---
        // You MUST update this with approximate centers for your barangays.
        // Names must EXACTLY match the database/signup options.
        const barangayCoordinateLookup = {
            // == [ ACTION REQUIRED ] ==
            // Replace these demo coordinates with real ones for your service barangays.
            // Find coordinates using Google Maps (right-click -> "What's here?").
            "Cataguingtingan": [16.239948, 120.468656], // Example - Please verify
            "Poblacion East": [16.2143, 120.4913],    // Example - Please verify
            "Poblacion West": [16.2134, 120.5018],    // Example - Please verify
            "Subusub": [16.2287, 120.4597],          // Example - Please verify
             "Bani": [16.2465, 120.4878],             // Example - Please verify
            // Add ALL other barangays available during signup here
            // =========================
        };
        
        // --- Get the user's barangay from PHP ---
        const USER_BARANGAY_NAME = <?php echo json_encode($user_barangay); ?>;

        let modalMap; // Holds the modal map instance
        let userMarker; // Holds the draggable green marker
        let geocoder = L.Control.Geocoder.nominatim(); // Geocoder for finding address
        
        // --- NEW: Define Delivery Radius ---
        // How close (in meters) the user's pin must be to a blue delivery point.
        // Adjust this value based on how far your drivers will travel from a point.
        // 1000 meters = 1 kilometer
        const DELIVERY_RADIUS_METERS = 3000; // Example: 3km radius around each blue pin

        // --- INITIALIZE MAIN PAGE MAP ---
        document.addEventListener('DOMContentLoaded', function() {
            const mainMap = L.map('main-page-map').setView([16.245, 120.47], 13); // Centered on Rosario
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(mainMap);

            // Add blue markers for service points to the main map
            serviceAreaPoints.forEach(coords => {
                L.marker(coords).addTo(mainMap).bindPopup('We deliver near this point.');
            });
        });

        // --- MODAL AND LOCATION CHECKING LOGIC ---
        const locationModalElement = document.getElementById('locationCheckModal');
        const locationModal = new bootstrap.Modal(locationModalElement);
        
        function openLocationCheckModal() {
            locationModal.show();
        }
        
        // Initialize map & add logic AFTER the modal is fully shown
        locationModalElement.addEventListener('shown.bs.modal', function() {
            if (!modalMap) { // Initialize map only the first time modal opens
                
                // Find initial coordinates for the user's barangay, default if not found
                let initialCoords = barangayCoordinateLookup[USER_BARANGAY_NAME] || [16.245, 120.47]; // Default to center if lookup fails
                
                modalMap = L.map('location-check-map').setView(initialCoords, 15); // Start zoomed in
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(modalMap);
                
                // Add all BLUE service area markers to the modal map
                serviceAreaPoints.forEach(coords => {
                    L.marker(coords).addTo(modalMap);
                });

                // Add the DRAGGABLE GREEN marker for the user
                userMarker = L.marker(initialCoords, {
                    draggable: true,
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                        iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
                    })
                }).addTo(modalMap);

                // --- EVENT LISTENER FOR MARKER DRAG ---
                userMarker.on('dragend', function(event) {
                    const marker = event.target;
                    const position = marker.getLatLng(); // Get the new coordinates after dragging
                    console.log('Marker dragged to:', position);
                    validateMarkerLocation(position); // Validate the new location
                    reverseGeocode(position); // Find address for the new location
                });

            } else {
                 // If map already exists, just ensure size is correct
                 modalMap.invalidateSize(); 
                 // Optionally, reset view to initial barangay coords if needed
                 // let initialCoords = barangayCoordinateLookup[USER_BARANGAY_NAME] || [16.245, 120.47];
                 // modalMap.setView(initialCoords, 15);
                 // userMarker.setLatLng(initialCoords); 
            }
            
            // --- Initial check and geocode when modal opens ---
            const initialPosition = userMarker.getLatLng();
            validateMarkerLocation(initialPosition);
            reverseGeocode(initialPosition);
            userMarker.bindPopup('Drag this pin to your exact house location.').openPopup();

        });

        // --- NEW: Function to Validate Marker Location ---
        function validateMarkerLocation(userLatLng) {
            const statusDiv = document.getElementById('locationStatus');
            statusDiv.className = 'mt-3 text-center fw-bold'; // Reset class
            
            let isInside = false;
            // Check distance to ALL blue service points
            for (const point of serviceAreaPoints) {
                const servicePointLatLng = L.latLng(point[0], point[1]);
                const distance = userLatLng.distanceTo(servicePointLatLng); // Distance in meters

                console.log(`Distance to point ${point}: ${distance} meters`); // For debugging

                if (distance <= DELIVERY_RADIUS_METERS) {
                    isInside = true;
                    break; // Found a point within range, no need to check further
                }
            }

            // Update status message based on validation
            if (isInside) {
                statusDiv.innerHTML = '<span class="text-success">✅ Great! Your selected location is within our delivery area.</span>';
            } else {
                statusDiv.innerHTML = '<span class="text-danger">❌ Sorry, your selected location appears to be outside our service radius.</span>';
            }
        }

        // --- NEW: Function for Reverse Geocoding (Address Lookup) ---
        function reverseGeocode(latlng) {
            const addressDiv = document.getElementById('addressDisplay');
            addressDiv.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Finding address...';

            geocoder.reverse(latlng, modalMap.options.crs.scale(modalMap.getZoom()), function(results) {
                if (results && results.length > 0 && results[0].name) {
                     addressDiv.textContent = results[0].name; // Display the found address
                } else {
                    addressDiv.textContent = 'Could not find address for this location.';
                 }
            }, this);
        }

    </script>
</body>
</html>