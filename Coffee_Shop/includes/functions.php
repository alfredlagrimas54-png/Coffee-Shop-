<?php
declare(strict_types=1);

function admin_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function product_categories(): array
{
    return [
        'coffee' => 'Coffee',
        'iced-drinks' => 'Iced Drinks',
        'non-coffee' => 'Non-Coffee',
        'pastries' => 'Pastries',
        'light-meals' => 'Light Meals',
    ];
}

function require_admin(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php?return=admin/dashboard.php');
        exit;
    }

    try {
        $statement = database_connection()->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => (int) $_SESSION['user_id']]);
        $role = $statement->fetchColumn();
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Admin access could not be verified.');
    }

    if ($role !== 'admin') {
        unset($_SESSION['user_role']);
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Admin access required.');
    }

    $_SESSION['user_role'] = 'admin';
}

function valid_csrf_token($submittedToken): bool
{
    return is_string($submittedToken) && hash_equals(csrf_token(), $submittedToken);
}
