<?php
/**
 * Online Farm Market - Header Template
 *
 * Renders the page head, top navigation bar, cart count,
 * user status, and flash message banner.
 */

$currentUser = user();
$flashMessage = flash();
$cartCount = array_sum($_SESSION['cart'] ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Online Farm Market</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header>
    <nav class="nav">
        <!-- Site Logo / Brand -->
        <a class="brand" href="index.php">🌾 Online Farm Market</a>

        <!-- Navigation Links -->
        <div class="links">
            <a href="index.php">Shop</a>

            <?php if ($currentUser && ($currentUser['role'] ?? '') === 'customer'): ?>
                <a href="cart.php">
                    Cart <?php if ($cartCount > 0): ?><span class="badge"><?= $cartCount ?></span><?php endif; ?>
                </a>
                <a href="orders.php">My Orders</a>
            <?php elseif ($currentUser && ($currentUser['role'] ?? '') === 'farmer'): ?>
                <a href="farmer.php">Farmer Dashboard</a>
            <?php endif; ?>

            <?php if ($currentUser): ?>
                <span class="who">👤 <?= h($currentUser['name']) ?> (<?= h($currentUser['role']) ?>)</span>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<main class="wrap">
    <!-- Flash Messages (Success / Error alerts) -->
    <?php if ($flashMessage): ?>
        <div class="flash <?= h($flashMessage[1]) ?>">
            <?= $flashMessage[1] === 'ok' ? '✓' : '⚠' ?>
            <?= h($flashMessage[0]) ?>
        </div>
    <?php endif; ?>
