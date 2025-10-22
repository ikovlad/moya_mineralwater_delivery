<?php
// Initialize the session and include config
session_start();
require_once "config.php";

// 1. --- VALIDATION ---
// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.html");
    exit;
}

// Check for POST request
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: order.php");
    exit;
}

$user_id = $_SESSION["id"];

// 2. --- GATHER AND PROCESS CART ITEMS ---
$cart_items = [];
$product_ids_to_fetch = [];

// The form sends an array like: product_ids[PRODUCT_ID] = "QUANTITY_FIELD_NAME"
// We loop through this to find which products were actually ordered.
if (isset($_POST['product_ids']) && is_array($_POST['product_ids'])) {
    foreach ($_POST['product_ids'] as $product_id => $qty_field_name) {
        $product_id = filter_var($product_id, FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, $qty_field_name, FILTER_VALIDATE_INT);

        // Only add items that have a valid ID and a quantity greater than 0
        if ($product_id && $quantity > 0) {
            $cart_items[$product_id] = $quantity;
            $product_ids_to_fetch[] = $product_id;
        }
    }
}

// If after checking everything, the cart is empty, redirect back.
if (empty($cart_items)) {
    $_SESSION['order_error'] = "Your cart is empty. Please select at least one item.";
    header("location: order.php");
    exit;
}

// 3. --- FETCH SECURE PRICES & CALCULATE TOTAL ---
$product_prices = [];
$grand_total = 0;

// Create placeholders for the SQL IN() clause (e.g., ?,?,?)
$ids_placeholder = implode(',', array_fill(0, count($product_ids_to_fetch), '?'));
$sql_prices = "SELECT id, price FROM products WHERE id IN ($ids_placeholder)";

if ($stmt_prices = mysqli_prepare($conn, $sql_prices)) {
    $types = str_repeat('i', count($product_ids_to_fetch));
    mysqli_stmt_bind_param($stmt_prices, $types, ...$product_ids_to_fetch);
    mysqli_stmt_execute($stmt_prices);
    $result_prices = mysqli_stmt_get_result($stmt_prices);
    
    while ($row = mysqli_fetch_assoc($result_prices)) {
        // Store the price from the database, not from the form
        $product_prices[$row['id']] = (float)$row['price'];
    }
    mysqli_stmt_close($stmt_prices);
}

// Calculate the grand total securely using prices from the database
foreach ($cart_items as $product_id => $quantity) {
    if (isset($product_prices[$product_id])) {
        $grand_total += $quantity * $product_prices[$product_id];
    } else {
        // If a product ID from the cart doesn't have a price, something is wrong.
        $_SESSION['order_error'] = "An error occurred with an invalid item. Please try again.";
        header("location: order.php");
        exit;
    }
}

// 4. --- DATABASE TRANSACTION ---
// Start a transaction. This ensures that BOTH the main order AND all its items are saved, or nothing is.
mysqli_begin_transaction($conn);

try {
    // A. INSERT THE MAIN ORDER into the `orders` table
    $sql_main_order = "INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'Pending')";
    $stmt_main_order = mysqli_prepare($conn, $sql_main_order);
    mysqli_stmt_bind_param($stmt_main_order, "id", $user_id, $grand_total);
    mysqli_stmt_execute($stmt_main_order);

    // Get the ID of the order we just created
    $new_order_id = mysqli_insert_id($conn);
    if ($new_order_id == 0) {
        throw new Exception("Failed to create main order record.");
    }

    // B. INSERT EACH ITEM into the `order_items` table
    $sql_items = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)";
    $stmt_items = mysqli_prepare($conn, $sql_items);

    foreach ($cart_items as $product_id => $quantity) {
        $unit_price = $product_prices[$product_id];
        $subtotal = $quantity * $unit_price;
        mysqli_stmt_bind_param($stmt_items, "iiidd", $new_order_id, $product_id, $quantity, $unit_price, $subtotal);
        mysqli_stmt_execute($stmt_items);
    }
    
    // C. If everything was successful, commit the changes to the database
    mysqli_commit($conn);
    
    // 5. --- REDIRECT TO SUCCESS PAGE ---
    $_SESSION['order_success_id'] = $new_order_id; // Pass the new order ID to the success page
    header("location: order_success.php");
    exit;

} catch (Exception $e) {
    // D. If any step failed, roll back all changes
    mysqli_rollback($conn);
    
    // Log the detailed error for the admin/developer to see
    error_log("Order processing failed: " . $e->getMessage());

    // Show a generic error to the user and redirect
    $_SESSION['order_error'] = "Your order could not be placed due to a system error. Please try again.";
    header("location: order.php");
    exit;
} finally {
    // Close the database connection
    mysqli_close($conn);
}
?>