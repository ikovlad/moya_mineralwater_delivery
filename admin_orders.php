<?php
require_once "config.php";
include 'admin_header.php';

// Fetch all orders with user details
$orders_query = "
    SELECT o.id, u.full_name, u.phone_number, u.address_detail, u.address_barangay, o.order_date, o.status, o.total_amount
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY 
        CASE o.status
            WHEN 'Pending' THEN 1
            WHEN 'Confirmed' THEN 2
            WHEN 'On the Way' THEN 3
            WHEN 'Delivered' THEN 4
            ELSE 5 
        END, o.order_date DESC"; // Show active orders first
$orders_result = $conn->query($orders_query);
?>
<div class="container-fluid">
    <h1 class="page-header">Order Management</h1>

    <div class="card">
        <div class="card-header">All Orders</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Admin Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders_result->num_rows > 0): ?>
                            <?php while($order = $orders_result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['address_detail'] . ', ' . $order['address_barangay']); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></td>
                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <?php 
                                        $status = htmlspecialchars($order['status']);
                                        $badge_class = '';
                                        switch ($status) {
                                            case 'Pending': $badge_class = 'bg-warning text-dark'; break;
                                            case 'Confirmed': $badge_class = 'bg-info text-dark'; break;
                                            case 'On the Way': $badge_class = 'bg-primary'; break;
                                            case 'Delivered': $badge_class = 'bg-success'; break;
                                            case 'Completed': $badge_class = 'bg-secondary'; break; // Changed completed color slightly
                                            case 'Cancelled': $badge_class = 'bg-danger'; break;
                                            default: $badge_class = 'bg-light text-dark';
                                        }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?> p-2"><?php echo $status; ?></span>
                                </td>
                                <td>
                                    <?php 
                                        $current_status = $order['status'];
                                        $is_final_state = in_array($current_status, ['Completed', 'Cancelled']);
                                    ?>
                                    
                                    <?php if ($is_final_state): ?>
                                        <span class="text-muted fst-italic">No action needed</span>
                                    <?php else: ?>
                                        <select class="form-select form-select-sm" 
                                                onchange="updateOrderStatus(<?php echo $order['id']; ?>, this.value)" 
                                                <?php // Disable if waiting for user action like 'On the Way' or 'Delivered' unless cancelling
                                                    // if (in_array($current_status, ['On the Way', 'Delivered'])) echo 'disabled'; 
                                                ?>>
                                            
                                            <option value="<?php echo $current_status; ?>" selected disabled hidden><?php echo $current_status; ?> (Change?)</option>
                                            
                                            <?php if ($current_status == 'Pending'): ?>
                                                <option value="Confirmed">Confirm Order</option>
                                                
                                            <?php elseif ($current_status == 'Confirmed'): ?>
                                                <option value="On the Way">Set Out for Delivery</option>
                                                
                                            <?php elseif ($current_status == 'On the Way'): ?>
                                                <option value="On the Way" disabled>Waiting for user confirmation...</option>

                                            <?php elseif ($current_status == 'Delivered'): ?>
                                                <option value="Completed">Mark as Completed</option>

                                            <?php endif; ?>

                                            <option value="Cancelled">Cancel Order</option>
                                            
                                        </select>
                                         <?php if (in_array($current_status, ['On the Way', 'Delivered'])): ?>
                                             <small class="text-muted d-block mt-1">Waiting for user</small>
                                         <?php endif; ?>
                                    <?php endif; ?>
                                    </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted p-4">No orders found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function updateOrderStatus(orderId, newStatus) {
    // Confirmation for cancelling
    if (newStatus === 'Cancelled') {
        if (!confirm('Are you sure you want to cancel Order #' + orderId + '? This cannot be undone.')) {
            // Reset the dropdown if the user cancels the confirmation
            location.reload(); // Simple way to reset the dropdown state
            return; 
        }
    }
    
    fetch('update_order_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `order_id=${orderId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Reload the page to show the updated status and potentially changed actions
            location.reload(); 
        } else {
            alert('Failed to update status: ' + (data.message || 'Unknown error'));
            location.reload(); // Reload even on failure to reset dropdown
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while trying to update the status.');
        location.reload(); // Reload on network or other errors
    });
}
</script>
<?php include 'admin_footer.php'; ?>