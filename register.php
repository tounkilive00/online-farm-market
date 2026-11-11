<?php
/**
 * Online Farm Market - User Registration
 *
 * Allows new customers and farmers to register accounts.
 * Automatically initializes farmer profile records upon registration.
 */

require_once __DIR__ . '/db.php';

// Handle registration submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $name     = trim($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'customer';

    // Validate inputs
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('Please provide a valid name and email address.', 'err');
    } elseif (strlen($password) < 6) {
        flash('Password must be at least 6 characters long.', 'err');
    } elseif (!in_array($role, ['customer', 'farmer'], true)) {
        flash('Please select a valid account role.', 'err');
    } elseif (q('SELECT 1 FROM users WHERE email = ?', [$email])->fetch()) {
        flash('An account with this email address is already registered.', 'err');
    } else {
        // Hash password securely with bcrypt
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user record and retrieve generated user id
        $userId = q(
            'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?) RETURNING id',
            [$name, $email, $passwordHash, $role]
        )->fetchColumn();

        // If registered as a farmer, create an initial farmer profile record
        if ($role === 'farmer') {
            q(
                'INSERT INTO farmer_profiles (user_id, farm_name) VALUES (?, ?)',
                [$userId, $name . "'s Farm"]
            );
        }

        flash('Your account has been created successfully! Please log in below.');
        redirect('login.php');
    }
}

include __DIR__ . '/header.php';
?>

<div class="card narrow">
    <h2>Create an Account</h2>
    <p class="muted">Join Online Farm Market as a customer to buy produce, or as a farmer to sell your harvest.</p>

    <form method="post" action="register.php">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">

        <label for="name">Full Name</label>
        <input id="name" type="text" name="name" placeholder="John Doe" value="<?= h($_POST['name'] ?? '') ?>" required autofocus>

        <label for="email">Email Address</label>
        <input id="email" type="email" name="email" placeholder="name@example.com" value="<?= h($_POST['email'] ?? '') ?>" required>

        <label for="password">Password (minimum 6 characters)</label>
        <input id="password" type="password" name="password" placeholder="••••••••" minlength="6" required>

        <label for="role">I want to register as a</label>
        <select id="role" name="role" required>
            <option value="customer" <?= (($_POST['role'] ?? '') === 'customer') ? 'selected' : '' ?>>Customer (Buy fresh produce)</option>
            <option value="farmer" <?= (($_POST['role'] ?? '') === 'farmer') ? 'selected' : '' ?>>Farmer (Sell produce & manage farm)</option>
        </select>

        <button type="submit" style="width: 100%;">Create Account</button>
    </form>

    <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 24px 0;">

    <p style="text-align: center; margin-bottom: 0;">
        Already have an account? <a href="login.php"><strong>Log in here</strong></a>
    </p>
</div>

<?php include __DIR__ . '/footer.php'; ?>
