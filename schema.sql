-- ==========================================================================
-- Online Farm Market - PostgreSQL Database Schema
-- ==========================================================================
-- Simple, clean 7-table schema connecting customers and local farmers.
-- Run this script or execute: php setup_db.php
-- ==========================================================================

DROP TABLE IF EXISTS reviews CASCADE;
DROP TABLE IF EXISTS order_items CASCADE;
DROP TABLE IF EXISTS orders CASCADE;
DROP TABLE IF EXISTS products CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS farmer_profiles CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- 1. Users (Customers & Farmers)
CREATE TABLE users (
    id            SERIAL PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(10)  NOT NULL CHECK (role IN ('customer', 'farmer')),
    created_at    TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- 2. Farmer Profiles
CREATE TABLE farmer_profiles (
    user_id        INT PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    farm_name      VARCHAR(150) NOT NULL DEFAULT '',
    bio            TEXT NOT NULL DEFAULT '',
    photo_url      VARCHAR(255) NOT NULL DEFAULT '',
    location       VARCHAR(150) NOT NULL DEFAULT '',
    latitude       DOUBLE PRECISION NOT NULL DEFAULT 0,
    longitude      DOUBLE PRECISION NOT NULL DEFAULT 0,
    payout_account VARCHAR(100) NOT NULL DEFAULT ''
);

-- 3. Produce Categories / Departments
CREATE TABLE categories (
    id   SERIAL PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE
);

-- 4. Products (Produce Listings)
CREATE TABLE products (
    id          SERIAL PRIMARY KEY,
    farmer_id   INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    category_id INT NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    title       VARCHAR(150) NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    photo_url   VARCHAR(255) NOT NULL DEFAULT '',
    price       NUMERIC(10,2) NOT NULL CHECK (price >= 0),
    stock       INT NOT NULL CHECK (stock >= 0),
    created_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 5. Customer Orders
CREATE TABLE orders (
    id               SERIAL PRIMARY KEY,
    customer_id      INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    total            NUMERIC(12,2) NOT NULL,
    delivery_address VARCHAR(255) NOT NULL,
    status           VARCHAR(20) NOT NULL DEFAULT 'Pending',
    created_at       TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 6. Order Line Items
CREATE TABLE order_items (
    id         SERIAL PRIMARY KEY,
    order_id   INT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id INT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    quantity   INT NOT NULL CHECK (quantity > 0),
    unit_price NUMERIC(10,2) NOT NULL
);

-- 7. Farm Reviews
CREATE TABLE reviews (
    id          SERIAL PRIMARY KEY,
    farmer_id   INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    customer_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    rating      INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment     TEXT NOT NULL DEFAULT '',
    created_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Indexes for performance
CREATE INDEX idx_products_farmer ON products(farmer_id);
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_reviews_farmer ON reviews(farmer_id);
CREATE INDEX idx_orders_customer ON orders(customer_id);

-- Initial Categories
INSERT INTO categories (name) VALUES
    ('Vegetables'),
    ('Fruits'),
    ('Dairy & Eggs'),
    ('Meat & Poultry'),
    ('Grains & Bakery'),
    ('Honey & Preserves');
