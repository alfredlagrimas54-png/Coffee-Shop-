<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

$returnTo = $_POST['return'] ?? $_GET['return'] ?? 'buy-now.php';
if (!in_array($returnTo, ['buy-now.php', 'home.php'], true)) {
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
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '' || strlen($name) > 120) {
            $errorMessage = 'Enter your name (up to 120 characters).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errorMessage = 'Enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $errorMessage = 'Use a password with at least 8 characters.';
        } else {
            try {
                $connection = database_connection();
                $statement = $connection->prepare(
                    'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)'
                );
                $statement->execute([
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $connection->lastInsertId();
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'customer';
                header('Location: ' . $returnTo);
                exit;
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $errorMessage = 'An account already exists for that email.';
                } else {
                    error_log($exception->getMessage());
                    http_response_code(500);
                    $errorMessage = 'We could not create your account. Please try again later.';
                }
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
    <title>Create Account | Flooded Coffee Shop</title>
    <link rel="stylesheet" href="style.css?v=3">
</head>
<body class="auth-page">
    <?php require __DIR__ . '/navigation.php'; ?>
    <main class="account-page">
        <h1>Create Account</h1>
        <p class="account-intro">Create your account to place orders and view your order history.</p>
        <?php if ($errorMessage !== ''): ?>
            <p class="account-message account-error" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form class="account-form account-form-single" method="post" action="signup.php">
            <input type="hidden" name="csrf_token" value="<?= $token ?>">
            <input type="hidden" name="return" value="<?= $escapedReturn ?>">
            <label>Name
                <input type="text" name="name" maxlength="120" autocomplete="name" required>
            </label>
            <label>Email
                <input type="email" name="email" maxlength="254" autocomplete="email" required>
            </label>
            <label>Password (at least 8 characters)
                <input type="password" name="password" minlength="8" autocomplete="new-password" required>
            </label>
            <button type="submit">Create Account</button>
            <p class="account-switch">Already have an account? <a href="login.php?return=<?= rawurlencode($returnTo) ?>">Sign in</a>.</p>
        </form>
    </main>
    <?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
