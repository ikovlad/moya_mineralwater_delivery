<?php
// get_sales_data.php (Final Correct Version)

// 1. --- START SESSION ---
session_start();

// 2. --- DEBUGGING & HEADERS ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate"); // Prevent caching issues
header("Pragma: no-cache");
header("Expires: 0");

// Include the database configuration
require_once "config.php";

// 3. --- SECURITY CHECK ---
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    echo json_encode(['error' => 'Unauthorized. Admin not logged in.']);
    exit;
}

// 4. --- DATABASE QUERY ---
$sql = "SELECT
            DATE(order_date) as order_day,
            SUM(total_amount) as daily_total
        FROM orders
        WHERE
            order_date >= CURDATE() - INTERVAL 30 DAY
        GROUP BY
            order_day
        ORDER BY
            order_day ASC";

// Use error handling for the query execution
$result = $conn->query($sql);

// 5. --- ERROR HANDLING ---
if ($result === false) { // Check specifically for false, indicating a query error
    echo json_encode(['error' => 'Database query failed: ' . $conn->error]);
    $conn->close();
    exit;
}

$labels = [];
$values = [];

// Check if there are any results before fetching
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $labels[] = date('M d', strtotime($row['order_day']));
        // Ensure value is numeric, default to 0 if null
        $values[] = $row['daily_total'] ?? 0;
    }
}
// Free the result set
$result->free();

$conn->close();

// 6. --- SEND FINAL RESPONSE ---
// Always send valid JSON, even if arrays are empty
echo json_encode(['labels' => $labels, 'values' => $values]);
exit;
?>