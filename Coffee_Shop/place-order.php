<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, ['error' => 'Method not allowed.']);
}

if (!isset($_SESSION['user_id'])) {
    respond(401, ['error' => 'Sign in before placing your order.']);
}

$submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!is_string($submittedToken) || !hash_equals(csrf_token(), $submittedToken)) {
    respond(400, ['error' => 'Your session expired. Reload the page and try again.']);
}

try {
    $pdo = database_connection();
    $pdo->beginTransaction();
    $cartStatement = $pdo->prepare(
        'SELECT item_name, unit_price, quantity, line_total
         FROM cart_added
         WHERE user_id = :user_id AND order_id IS NULL
         FOR UPDATE'
    );
    $cartStatement->execute(['user_id' => (int) $_SESSION['user_id']]);
    $cartItems = $cartStatement->fetchAll();
    if ($cartItems === []) {
        $pdo->rollBack();
        respond(422, ['error' => 'Your cart is empty.']);
    }

    $total = array_sum(array_map(
        static fn (array $item): float => (float) $item['line_total'],
        $cartItems
    ));

    $orderStatement = $pdo->prepare(
        "INSERT INTO orders (user_id, status, total) VALUES (:user_id, 'pending', :total)"
    );
    $orderStatement->execute([
        'user_id' => (int) $_SESSION['user_id'],
        'total' => round($total, 2),
    ]);
    $orderId = (int) $pdo->lastInsertId();

    $attachCartStatement = $pdo->prepare(
        'UPDATE cart_added
         SET order_id = :order_id, cart_key = NULL
         WHERE user_id = :user_id AND order_id IS NULL'
    );
    $attachCartStatement->execute([
        'order_id' => $orderId,
        'user_id' => (int) $_SESSION['user_id'],
    ]);

    $pdo->commit();
    respond(201, ['order_id' => $orderId, 'total' => round($total, 2)]);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($exception->getMessage());
    respond(500, ['error' => 'We could not place your order. Please try again later.']);
}
