<?php

require_once __DIR__ . '/db.php';

// Ensure the user is logged in as a customer
$currentUser = require_role('customer');

// Retrieve all orders for this customer ordered by newest first
$orders = q(
    'SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC',
    [$currentUser['id']]
)->fetchAll();

// Pre-fetch all order items for the retrieved orders to avoid N+1 query overhead
$itemsByOrderId = [];

if (!empty($orders)) {
    $orderIds     = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

    $itemsQuery = "SELECT oi.*, p.title AS product_title 
                   FROM order_items oi 
                   LEFT JOIN products p ON p.id = oi.product_id 
                   WHERE oi.order_id IN ($placeholders)
                   ORDER BY oi.id ASC";

    $allItems = q($itemsQuery, $orderIds)->fetchAll();

    foreach ($allItems as $item) {
        $itemsByOrderId[$item['order_id']][] = $item;
    }
}

include __DIR__ . '/header.php';
?>

<h2>My Orders</h2>

<?php if (empty($orders)): ?>
    <div class="card" style="text-align: center; padding: 48px;">
        <span style="font-size: 3rem;">📦</span>
        <h3>You haven't placed any orders yet</h3>
        <p class="muted">Once you order fresh produce from our local farmers, track them here.</p>
        <a href="index.php" class="btn">Shop Fresh Produce</a>
    </div>
<?php else: ?>
    <?php foreach ($orders as $order): ?>
        <?php
            $statusClass = 'badge-pending';
            $statusLower = strtolower($order['status']);
            if ($statusLower === 'delivered') {
                $statusClass = 'badge-delivered';
            } elseif ($statusLower === 'processing' || $statusLower === 'dispatched') {
                $statusClass = 'badge-processing';
            }
        ?>
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                <div>
                    <strong style="font-size: 1.15rem;">Order #<?= (int)$order['id'] ?></strong>
                    <span class="badge-status <?= $statusClass ?>" style="margin-left: 8px;">
                        <?= h($order['status']) ?>
                    </span>
                </div>
                <div style="font-size: 1.2rem; font-weight: 700; color: var(--primary);">
                    $<?= money($order['total']) ?>
                </div>
            </div>

            <p class="muted" style="margin: 4px 0 14px;">
                Placed on <?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?> &middot;
                Delivery address: <strong><?= h($order['delivery_address']) ?></strong>
            </p>

            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th style="width: 100px;">Quantity</th>
                        <th style="width: 130px;">Price Each</th>
                        <th style="width: 130px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $orderItems = $itemsByOrderId[$order['id']] ?? [];
                        foreach ($orderItems as $item): 
                            $lineSubtotal = (float)$item['unit_price'] * (int)$item['quantity'];
                    ?>
                        <tr>
                            <td><?= h($item['product_title'] ?: 'Produce Item') ?></td>
                            <td>&times; <?= (int)$item['quantity'] ?></td>
                            <td>$<?= money($item['unit_price']) ?></td>
                            <td>$<?= money($lineSubtotal) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
