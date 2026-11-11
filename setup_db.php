<?php
/**
 * Online Farm Market - Database Setup & Seeder Script
 *
 * Resets the PostgreSQL database with the clean schema and initial demo data.
 * Usage: php setup_db.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$pdo = db();

echo "Resetting database schema...\n";
$schemaSql = file_get_contents(__DIR__ . '/schema.sql');
$pdo->exec($schemaSql);

echo "Seeding demo accounts and starter produce...\n";

// Demo Password: password123
$demoPassword = password_hash('password123', PASSWORD_DEFAULT);

// 1. Demo Farmer
$userStmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?) RETURNING id");
$userStmt->execute(['Green Valley Farms', 'farmer@farm.com', $demoPassword, 'farmer']);
$farmerId = $userStmt->fetchColumn();

$pdo->prepare("
    INSERT INTO farmer_profiles (user_id, farm_name, bio, photo_url, location, latitude, longitude, payout_account)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
")->execute([
    $farmerId,
    'Green Valley Organic Farm',
    'Family-owned 50-acre sustainable farm producing certified organic vegetables, raw honey, and fresh daily eggs.',
    'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?auto=format&fit=crop&w=600&q=80',
    'Lancaster County, PA',
    40.0379,
    -76.3055,
    'US9876543210'
]);

// 2. Demo Customer
$userStmt->execute(['Alice Cooper', 'customer@market.com', $demoPassword, 'customer']);
$customerId = $userStmt->fetchColumn();

// 3. Category IDs
$catMap = $pdo->query("SELECT name, id FROM categories")->fetchAll(PDO::FETCH_KEY_PAIR);

// 4. Starter Produce
$prodStmt = $pdo->prepare("
    INSERT INTO products (farmer_id, category_id, title, description, photo_url, price, stock)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$starterProducts = [
    [$catMap['Vegetables'], 'Heirloom Vine Tomatoes (1 kg)', 'Vine-ripened, pesticide-free heirloom tomatoes bursting with natural sweetness.', 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?auto=format&fit=crop&w=600&q=80', 4.50, 35],
    [$catMap['Fruits'], 'Crisp Honeycrisp Apples (Bag of 6)', 'Hand-picked autumn Honeycrisp apples with a juicy, snap-crisp texture.', 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?auto=format&fit=crop&w=600&q=80', 5.25, 40],
    [$catMap['Dairy & Eggs'], 'Pasture-Raised Brown Eggs (Dozen)', 'Farm-fresh Grade-A brown eggs from pasture-roaming hens fed organic grain.', 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&w=600&q=80', 4.75, 28],
    [$catMap['Honey & Preserves'], 'Raw Wildflower Honey (500g)', '100% pure unfiltered raw wildflower honey harvested from our on-farm apiary.', 'https://images.unsplash.com/photo-1587049352846-4a222e784d38?auto=format&fit=crop&w=600&q=80', 8.99, 20],
    [$catMap['Grains & Bakery'], 'Artisan Sourdough Country Loaf', 'Naturally leavened stone-ground sourdough baked fresh daily with a golden crust.', 'https://images.unsplash.com/photo-1589367920969-ab8e050bbb04?auto=format&fit=crop&w=600&q=80', 6.50, 15],
    [$catMap['Vegetables'], 'Organic Baby Spinach (250g)', 'Tender, freshly harvested organic spinach leaves, washed and ready to enjoy.', 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?auto=format&fit=crop&w=600&q=80', 3.20, 25]
];

foreach ($starterProducts as $p) {
    $prodStmt->execute([$farmerId, $p[0], $p[1], $p[2], $p[3], $p[4], $p[5]]);
}

// 5. Sample Review
$pdo->prepare("
    INSERT INTO reviews (farmer_id, customer_id, rating, comment)
    VALUES (?, ?, ?, ?)
")->execute([
    $farmerId,
    $customerId,
    5,
    'The heirloom tomatoes and sourdough bread were unbelievably fresh! Delivery was on time and very friendly.'
]);

echo "Database ready!\n";
echo "Demo Accounts:\n";
echo "  - Farmer:   farmer@farm.com   / password123\n";
echo "  - Customer: customer@market.com / password123\n";
