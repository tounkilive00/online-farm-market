# 🌾 Online Farm Market - PHP & PostgreSQL

A clean, modern web application connecting local farmers directly with community customers. Built with vanilla PHP 8.1+ and PostgreSQL (PDO).

---

## 🚀 Features

- **Department Browsing & Real-time Search:** Filter fresh produce by department (Fruits, Vegetables, Dairy, etc.) and search across titles and descriptions.
- **Dual User Roles:**
  - **Customer:** Browse produce, add items to cart, checkout with transactional stock validation, track historical orders with status badges, and write star reviews for farms.
  - **Farmer:** Update public farm profile (bio, banner image, location coordinates, payout account), list new produce with department and photos, adjust inventory stock, and monitor sales revenue & itemized order stats in a live dashboard.
- **Transactional Checkout:** Atomic transactions with row-level locking (`FOR UPDATE`) to prevent race conditions and overselling.
- **Security Built-in:** CSRF protection tokens on all form mutations, `password_hash` with bcrypt, PDO prepared statements against SQL injection, and XSS sanitization via helper functions.
- **Modern Responsive Design:** Clean green-themed aesthetic, accessible tables, cards, stat badges, and mobile-friendly layouts.

---

## 🛠️ Requirements & Setup

1. **PHP 8.1+** with `pdo_pgsql` enabled.
   *(Already configured in `C:\php-8.5\php.ini`)*
2. **PostgreSQL** running locally on port `5432`.
   - Database name: `online-farm-market`
   - Default credentials configured in `config.php`:
     - Host: `localhost`
     - Port: `5432`
     - Database: `online-farm-market`
     - User: `postgres`
     - Password: `Tounkarababa201`

---

## 🏃 Running the Application

In this project directory, start PHP's built-in development web server:

```bash
php -S localhost:8000
```

Then open your browser to:
[http://localhost:8000](http://localhost:8000)

---

## 📂 Project Architecture

```
├── config.php            # Database credentials and environment overrides
├── db.php                # PDO singleton connection, helpers (h, q, flash, csrf, etc.)
├── schema.sql            # PostgreSQL table schema and initial category seed
├── seed_categories.php   # Utility script to populate product departments
├── header.php            # Global navigation bar, auth badge, and flash messages
├── footer.php            # Global page footer
├── index.php             # Produce catalog, department filters, and search
├── login.php             # User authentication and session management
├── logout.php            # Session termination handler
├── register.php          # Customer and Farmer account registration
├── cart.php              # Shopping cart & transactional order checkout
├── orders.php            # Customer order history and line-item breakdown
├── farm.php              # Public farm profile, produce list, and customer reviews
├── farmer.php            # Farmer portal (Profile, My Produce, Sales, Reviews)
└── assets/
    └── style.css         # Modern responsive CSS styling
```
