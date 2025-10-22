<?php
/**
 * Moya Water Delivery - Database Configuration
 * This file contains credentials for connecting to the MySQL database.
 * IMPORTANT: In a real-world scenario, these should be handled more securely (e.g., environment variables).
 */

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // <<< CHANGE THIS
define('DB_PASSWORD', ''); // <<< CHANGE THIS
define('DB_NAME', 'moya_delivery_db');
date_default_timezone_set('Asia/Manila');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false){
    die("ERROR: Could not connect to the database. " . mysqli_connect_error());
}

/**
 * Global function for safe data sanitization.
 * @param string $data The string to sanitize.
 * @return string The sanitized string.
 */
function sanitize_input($conn, $data) {
    // Remove whitespace and check for string type
    $data = trim($data);
    if (!is_string($data)) {
        return '';
    }
    // Escape special characters to prevent SQL injection
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

?>
