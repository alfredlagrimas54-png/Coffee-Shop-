<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

$returnTo = $_POST['return'] ?? $_GET['return'] ?? 'buy-now.php';
if (!in_array($returnTo, ['buy-now.php', 'home.php', 'admin/dashboard.php'], true)) {
    $returnTo = 'buy-now.php';
}

if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $returnTo);
    exit;
}

$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals(csrf_token(), $submittedToken)) {
        http_response_code(400);
        $errorMessage = 'Your session expired. Reload the page and try again.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errorMessage = 'Enter a valid email address.';
        } else {
            try {
                $statement = database_connection()->prepare(
                    'SELECT id, name, email, password_hash, role FROM users WHERE email = :email LIMIT 1'
                );
                $statement->execute(['email' => $email]);
                $user = $statement->fetch();
                if ($user && password_verify($password, $user['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    header('Location: ' . $returnTo);
                    exit;
                }

                $errorMessage = 'Email or password is incorrect.';
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                http_response_code(500);
                $errorMessage = 'We could not sign you in. Please try again later.';
            }
        }
    }
}

$token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
$escapedReturn = htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Flooded Coffee Shop</title>
    <link rel="stylesheet" href="style.css?v=3">
</head>
<body class="auth-page">
    <?php require __DIR__ . '/navigation.php'; ?>
    <main class="account-page">
        <h1>Sign In</h1>
        <?php if ($returnTo === 'buy-now.php'): ?>
            <p class="auth-return-notice">Please log in or create an account before placing an order.</p>
        <?php endif; ?>
        <p class="account-intro">Sign in to place orders and view your order history.</p>
        <?php if ($errorMessage !== ''): ?>
            <p class="account-message account-error" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif (isset($_GET['status']) && $_GET['status'] === 'logged-out'): ?>
            <p class="account-message" role="status">You have been signed out.</p>
        <?php endif; ?>
        <form class="account-form account-form-single" method="post" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= $token ?>">
            <input type="hidden" name="return" value="<?= $escapedReturn ?>">
            <label>Email
                <input type="email" name="email" maxlength="254" autocomplete="email" required>
            </label>
            <label>Password
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <button type="submit">Sign In</button>
            <p class="account-switch">New here? <a href="signup.php?return=<?= rawurlencode($returnTo) ?>">Create an account</a>.</p>
        </form>
    </main>
    <?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
