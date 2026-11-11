<?php
/**
 * Online Farm Market - User Login
 *
 * Authenticates registered customers and farmers using secure password hashing.
 */

require_once __DIR__ . '/db.php';

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        flash('Please provide both your email address and password.', 'err');
    } else {
        // Query user record by email
        $user = q('SELECT * FROM users WHERE email = ?', [$email])->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Prevent session fixation attack
            session_regenerate_id(true);

            // Store user session payload
            $_SESSION['user'] = [
                'id'    => (int)$user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ];

            flash('Welcome back, ' . $user['name'] . '!');

            // Redirect farmers to their management dashboard, customers to shopping catalog
            redirect($user['role'] === 'farmer' ? 'farmer.php' : 'index.php');
        } else {
            flash('Invalid email address or password. Please try again.', 'err');
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="card narrow">
    <h2>Log in to your account</h2>
    <p class="muted">Access your farm market orders or manage your produce listings.</p>

    <form method="post" action="login.php">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">

        <label for="email">Email Address</label>
        <input id="email" type="email" name="email" placeholder="name@example.com" value="<?= h($_POST['email'] ?? '') ?>" required autofocus>

        <label for="password">Password</label>
        <input id="password" type="password" name="password" placeholder="••••••••" required>

        <button type="submit" style="width: 100%;">Log In</button>
    </form>

    <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 24px 0;">

    <p style="text-align: center; margin-bottom: 0;">
        Don't have an account yet? <a href="register.php"><strong>Create an account</strong></a>
    </p>
</div>

<?php include __DIR__ . '/footer.php'; ?>
