<?php
/**
 * Online Farm Market - Database Helper & Session Core
 *
 * Provides database connection management, query execution helpers,
 * authentication helpers, CSRF protection, and flash messaging.
 */

require_once __DIR__ . '/config.php';

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Returns a shared PDO instance connecting to the PostgreSQL database.
 * Uses persistent singleton pattern across the request.
 *
 * @return PDO
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;options=\'--client_encoding=%s\'',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <title>Database Connection Error</title>
                <style>
                    body { font-family: Segoe UI, Arial, sans-serif; background: #fdf2f2; color: #7f1d1d; padding: 40px; margin: 0; }
                    .box { max-width: 600px; margin: 0 auto; background: #fff; border: 1px solid #fecaca; border-radius: 8px; padding: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
                    h2 { margin-top: 0; color: #b91c1c; }
                    code { background: #fee2e2; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; word-break: break-all; }
                </style>
            </head>
            <body>
                <div class="box">
                    <h2>Database Connection Failed</h2>
                    <p>Could not connect to PostgreSQL database <strong><?= htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8') ?></strong> at <code><?= htmlspecialchars(DB_HOST . ':' . DB_PORT, ENT_QUOTES, 'UTF-8') ?></code>.</p>
                    <p><strong>Error details:</strong><br><code><?= htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') ?></code></p>
                    <p>Please check your PostgreSQL service and credentials in <code>config.php</code>.</p>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }

    return $pdo;
}

/**
 * Prepares and executes an SQL statement with parameters.
 *
 * @param string $sql
 * @param array $params
 * @return PDOStatement
 */
function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Escapes a string for safe HTML output.
 *
 * @param mixed $value
 * @return string
 */
function h($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Formats a numeric value into a standard currency string (e.g. 12.50).
 *
 * @param mixed $number
 * @return string
 */
function money($number): string
{
    return number_format((float)$number, 2);
}

/**
 * Retrieves the currently authenticated user from the session, or null if guest.
 *
 * @return array|null
 */
function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Ensures the authenticated user has the specified role, otherwise redirects to login.
 *
 * @param string $role 'customer' or 'farmer'
 * @return array The authenticated user record
 */
function require_role(string $role): array
{
    $currentUser = user();
    if (!$currentUser || ($currentUser['role'] ?? '') !== $role) {
        flash('Please log in with appropriate permissions to access that page.', 'err');
        redirect('login.php');
    }
    return $currentUser;
}

/**
 * Sets or retrieves and clears a flash notification message.
 *
 * @param string|null $message If provided, sets the message; if null, retrieves and clears it.
 * @param string $type Message style type ('ok' or 'err')
 * @return array|null Array of [$message, $type] or null
 */
function flash(?string $message = null, string $type = 'ok'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = [$message, $type];
        return null;
    }

    $flashData = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flashData;
}

/**
 * Redirects the browser to a given URL and halts script execution.
 *
 * @param string $url
 * @return never
 */
function redirect(string $url): never
{
    header("Location: $url");
    exit;
}

/**
 * Generates or returns an existing CSRF token for form submission verification.
 *
 * @return string
 */
function csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

/**
 * Validates the CSRF token submitted via POST. Halts request if token is missing or invalid.
 *
 * @return void
 */
function check_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    $sessionToken = $_SESSION['csrf'] ?? null;

    if (!$sessionToken || !hash_equals($sessionToken, $token)) {
        http_response_code(400);
        die('Invalid or missing security token (CSRF). Please go back and try again.');
    }
}
