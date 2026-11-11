<?php

require_once __DIR__ . '/db.php';

// Retrieve search and category filters from query parameters
$searchQuery = trim($_GET['q'] ?? '');
$categoryId  = (int)($_GET['cat'] ?? 0);

// Base query selecting available products with category and farm information
$sql = 'SELECT p.*, c.name AS category_name, fp.farm_name, fp.location
        FROM products p
        JOIN categories c ON c.id = p.category_id
        LEFT JOIN farmer_profiles fp ON fp.user_id = p.farmer_id
        WHERE p.stock > 0';

$params = [];

// Apply search filter on title or description if provided
if ($searchQuery !== '') {
    $sql .= ' AND (p.title ILIKE ? OR p.description ILIKE ?)';
    $params[] = '%' . $searchQuery . '%';
    $params[] = '%' . $searchQuery . '%';
}

// Apply category filter if selected
if ($categoryId > 0) {
    $sql .= ' AND p.category_id = ?';
    $params[] = $categoryId;
}


$sql .= ' ORDER BY p.created_at DESC';
$products = q($sql, $params)->fetchAll();

// Retrieve all product categories for the filter dropdown
$categories = q('SELECT * FROM categories ORDER BY name ASC')->fetchAll();

include __DIR__ . '/header.php';
?>

<!-- Search and Filter Form -->
<form class="card row" method="get" action="index.php">
    <div style="flex: 2;">
        <label for="search-input">Search Produce</label>
        <input id="search-input" type="text" name="q" placeholder="Search by name, fruit, vegetable..." value="<?= h($searchQuery) ?>">
    </div>
    <div style="flex: 1;">
        <label for="category-select">Department</label>
        <select id="category-select" name="cat">
            <option value="0">All Departments</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                    <?= h($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="flex: 0 0 auto;">
        <button type="submit">Filter Produce</button>
        <?php if ($searchQuery !== '' || $categoryId > 0): ?>
            <a href="index.php" class="btn alt" style="margin-left: 8px;">Reset</a>
        <?php endif; ?>
    </div>
</form>

<!-- Product Catalog -->
<?php if (empty($products)): ?>
    <div class="card" style="text-align: center; padding: 48px;">
        <span style="font-size: 3rem;">🥕</span>
        <h3>No products found</h3>
        <p class="muted">Try adjusting your search terms or department filter.</p>
        <a href="index.php" class="btn alt">Browse all produce</a>
    </div>
<?php else: ?>
    <div class="grid">
        <?php foreach ($products as $product): ?>
            <div class="card">
                <!-- Produce Image or Placeholder -->
                <?php if (!empty($product['photo_url'])): ?>
                    <img class="produce-img" src="<?= h($product['photo_url']) ?>" alt="<?= h($product['title']) ?>" onerror="this.style.display='none'">
                <?php else: ?>
                    <div class="produce-img-placeholder">🥬</div>
                <?php endif; ?>

                <!-- Produce Details -->
                <h3><?= h($product['title']) ?></h3>
                
                <div class="muted" style="margin-bottom: 8px;">
                    <span><?= h($product['category_name']) ?></span>
                    <?php if (!empty($product['farm_name'])): ?>
                        &middot; <a href="farm.php?id=<?= (int)$product['farmer_id'] ?>">🏡 <?= h($product['farm_name']) ?></a>
                    <?php endif; ?>
                </div>

                <p><?= nl2br(h($product['description'])) ?></p>

                <div class="price-tag">
                    $<?= money($product['price']) ?>
                    <span class="muted" style="font-size: 0.85rem; font-weight: normal;">
                        (<?= (int)$product['stock'] ?> in stock)
                    </span>
                </div>

                <!-- Add to Cart or Login Prompt -->
                <div style="margin-top: 14px;">
                    <?php if ($currentUser && ($currentUser['role'] ?? '') === 'customer'): ?>
                        <form method="post" action="cart.php" style="display: flex; gap: 8px; align-items: center;">
                            <input type="hidden" name="csrf" value="<?= csrf() ?>">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                            <input type="number" name="qty" value="1" min="1" max="<?= (int)$product['stock'] ?>" style="width: 70px;" aria-label="Quantity">
                            <button type="submit" style="margin-top: 0; flex: 1;">Add to Cart</button>
                        </form>
                    <?php elseif (!$currentUser): ?>
                        <a href="login.php" class="btn alt" style="display: block; text-align: center; margin-top: 0;">Log in to purchase</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
