<?php
/**
 * Online Farm Market - Shopping Cart & Checkout
 *
 * Handles customer shopping cart items, quantity adjustments,
 * stock validation, and transactional order checkout.
 */

require_once __DIR__ . '/db.php';

// Only logged-in customers can access the shopping cart
$currentUser = require_role('customer');

// Reference active cart session array
$cart = &$_SESSION['cart'];
$cart ??= [];

// Handle cart actions (Add, Remove, Update, Checkout)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $action    = $_POST['action'] ?? '';
    $productId = (int)($_POST['id'] ?? 0);

    // 1. Add product to cart
    if ($action === 'add') {
        $quantity = max(1, (int)($_POST['qty'] ?? 1));
        $cart[$productId] = ($cart[$productId] ?? 0) + $quantity;
        flash('Produce added to your cart.');
        redirect('cart.php');
    }

    // 2. Remove product from cart
    if ($action === 'remove') {
        unset($cart[$productId]);
        flash('Item removed from cart.');
        redirect('cart.php');
    }

    // 3. Update quantity directly
    if ($action === 'update_qty') {
        $quantity = (int)($_POST['qty'] ?? 0);
        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = $quantity;
        }
        flash('Cart updated.');
        redirect('cart.php');
    }

    // 4. Checkout process (Atomic Transaction)
    if ($action === 'checkout' && !empty($cart)) {
        $deliveryAddress = trim($_POST['address'] ?? '');

        if ($deliveryAddress === '') {
            flash('Please enter a delivery address to complete your order.', 'err');
            redirect('cart.php');
        }

        $pdo = db();

        try {
            $pdo->beginTransaction();

            $orderTotal = 0;
            $orderLines = [];

            // Lock and inspect stock for each cart product
            foreach ($cart as $id => $requestedQty) {
                $stmt = $pdo->prepare('SELECT id, title, price, stock FROM products WHERE id = ? FOR UPDATE');
                $stmt->execute([$id]);
                $product = $stmt->fetch();

                if (!$product) {
                    throw new RuntimeException('One of the products in your cart is no longer available.');
                }

                if ((int)$product['stock'] < $requestedQty) {
                    throw new RuntimeException(
                        sprintf(
                            'Insufficient stock for "%s". Only %d remaining, but you requested %d.',
                            $product['title'],
                            $product['stock'],
                            $requestedQty
                        )
                    );
                }

                $lineTotal   = $product['price'] * $requestedQty;
                $orderTotal += $lineTotal;
                $orderLines[] = [
                    'product'  => $product,
                    'quantity' => $requestedQty,
                    'price'    => $product['price'],
                ];
            }

            // Create order record
            $orderInsertStmt = $pdo->prepare(
                'INSERT INTO orders (customer_id, total, delivery_address, status) VALUES (?, ?, ?, ?) RETURNING id'
            );
            $orderInsertStmt->execute([$currentUser['id'], $orderTotal, $deliveryAddress, 'Pending']);
            $orderId = $orderInsertStmt->fetchColumn();

            // Insert order items and deduct product stock
            $itemInsertStmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)'
            );
            $stockUpdateStmt = $pdo->prepare(
                'UPDATE products SET stock = stock - ? WHERE id = ?'
            );

            foreach ($orderLines as $line) {
                $itemInsertStmt->execute([
                    $orderId,
                    $line['product']['id'],
                    $line['quantity'],
                    $line['price'],
                ]);

                $stockUpdateStmt->execute([
                    $line['quantity'],
                    $line['product']['id'],
                ]);
            }

            // Commit transaction and clear shopping cart
            $pdo->commit();
            $cart = [];

            flash("Success! Order #$orderId has been placed. You can view its status under My Orders.");
            redirect('orders.php');

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('Checkout failed: ' . $e->getMessage(), 'err');
            redirect('cart.php');
        }
    }
}

// Fetch details for all products in the active cart
$cartItems = [];
$cartTotal = 0;

if (!empty($cart)) {
    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $productRows  = q(
        "SELECT p.*, c.name AS category_name, fp.farm_name 
         FROM products p
         JOIN categories c ON c.id = p.category_id
         LEFT JOIN farmer_profiles fp ON fp.user_id = p.farmer_id
         WHERE p.id IN ($placeholders)",
        array_keys($cart)
    )->fetchAll();

    foreach ($productRows as $row) {
        $qty       = (int)($cart[$row['id']] ?? 1);
        $subtotal  = $qty * (float)$row['price'];
        $cartTotal += $subtotal;

        $row['cart_qty'] = $qty;
        $row['subtotal'] = $subtotal;
        $cartItems[]     = $row;
    }
}

include __DIR__ . '/header.php';
?>

<h2>Your Shopping Cart</h2>

<?php if (empty($cartItems)): ?>
    <div class="card" style="text-align: center; padding: 48px;">
        <span style="font-size: 3rem;">🛒</span>
        <h3>Your cart is currently empty</h3>
        <p class="muted">Explore fresh vegetables, fruits, dairy, and artisanal goods from local farms.</p>
        <a href="index.php" class="btn">Start Shopping</a>
    </div>
<?php else: ?>
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Produce</th>
                    <th>Farm</th>
                    <th style="width: 130px;">Quantity</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                    <th style="width: 90px; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td>
                            <strong><?= h($item['title']) ?></strong>
                            <div class="muted"><?= h($item['category_name']) ?></div>
                        </td>
                        <td>
                            <a href="farm.php?id=<?= (int)$item['farmer_id'] ?>">
                                <?= h($item['farm_name'] ?: 'Local Farm') ?>
                            </a>
                        </td>
                        <td>
                            <form method="post" action="cart.php" style="display: flex; gap: 6px; align-items: center; margin: 0;">
                                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                                <input type="hidden" name="action" value="update_qty">
                                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                <input type="number" name="qty" value="<?= $item['cart_qty'] ?>" min="1" max="<?= (int)$item['stock'] ?>" style="width: 65px; padding: 4px 6px;">
                                <button type="submit" class="alt" style="padding: 4px 8px; margin: 0; font-size: 0.8rem;" title="Update quantity">Update</button>
                            </form>
                        </td>
                        <td>$<?= money($item['price']) ?></td>
                        <td><strong>$<?= money($item['subtotal']) ?></strong></td>
                        <td style="text-align: center;">
                            <form method="post" action="cart.php" style="margin: 0;">
                                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                <button type="submit" class="danger" style="padding: 5px 10px; margin: 0; font-size: 0.85rem;">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" style="text-align: right; font-size: 1.1rem;">Order Total:</th>
                    <th colspan="2" style="font-size: 1.25rem; color: var(--primary);">$<?= money($cartTotal) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Checkout Card -->
    <div class="card">
        <h3>Complete Your Order</h3>
        <p class="muted">Enter your delivery address for local fresh farm dispatch.</p>

        <form method="post" action="cart.php">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <input type="hidden" name="action" value="checkout">

            <label for="address">Local Delivery Address</label>
            <input id="address" type="text" name="address" placeholder="e.g. 124 Green Pasture Way, Apt 3B, Springfield" required>

            <button type="submit" style="font-size: 1rem; padding: 12px 24px;">
                Place Order ($<?= money($cartTotal) ?>)
            </button>
        </form>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
