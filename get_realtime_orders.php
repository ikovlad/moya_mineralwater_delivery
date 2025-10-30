<?php
require_once "config.php";

$sql = "SELECT o.id, u.full_name, o.total_amount, o.status, o.order_date
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status NOT IN ('Delivered', 'Completed', 'Cancelled')
        ORDER BY o.order_date DESC
        LIMIT 10";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>';
    echo '<tbody>';
    while($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>#' . $row['id'] . '</td>';
        echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
        echo '<td>₱' . number_format($row['total_amount'], 2) . '</td>';
        echo '<td><span class="badge bg-warning text-dark">' . htmlspecialchars($row['status']) . '</span></td>';
        echo '<td>' . date('M d, Y h:i A', strtotime($row['order_date'])) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<p class="text-center">No new orders at the moment.</p>';
}

$conn->close();
?>