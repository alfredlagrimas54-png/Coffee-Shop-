<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

header('Content-Type: application/json; charset=utf-8');

function respond_cart(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respond_cart(401, ['error' => 'Sign in to use your cart.']);
}

try {
    $pdo = database_connection();
    $userId = (int) $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $statement = $pdo->prepare(
            'SELECT id, item_name AS name, unit_price AS price, quantity
             FROM cart_added
             WHERE user_id = :user_id AND order_id IS NULL
             ORDER BY added_at, id'
        );
        $statement->execute(['user_id' => $userId]);
        respond_cart(200, ['items' => $statement->fetchAll()]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: GET, POST');
        respond_cart(405, ['error' => 'Method not allowed.']);
    }

    $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($submittedToken) || !hash_equals(csrf_token(), $submittedToken)) {
        respond_cart(400, ['error' => 'Your session expired. Reload the page and try again.']);
    }

    $request = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($request) || !isset($request['action'], $request['name']) || !is_string($request['name'])) {
        respond_cart(422, ['error' => 'The cart request is invalid.']);
    }

    $action = $request['action'];
    $name = $request['name'];
    if ($action === 'clear') {
        $statement = $pdo->prepare(
            'DELETE FROM cart_added WHERE user_id = :user_id AND order_id IS NULL'
        );
        $statement->execute(['user_id' => $userId]);
    } elseif ($action === 'add') {
        $quantity = $request['quantity'] ?? null;
        if (!is_int($quantity) || $quantity < 1 || $quantity > 20) {
            respond_cart(422, ['error' => 'Choose a quantity between 1 and 20.']);
        }

        $productStatement = $pdo->prepare(
            'SELECT price FROM products WHERE name = :name AND is_available = 1 LIMIT 1'
        );
        $productStatement->execute(['name' => $name]);
        $product = $productStatement->fetch();
        if (!$product) {
            respond_cart(404, ['error' => 'This menu item is unavailable.']);
        }

        $price = (float) $product['price'];
        $cartKey = hash('sha256', $userId . ':' . $name);
        $statement = $pdo->prepare(
            'INSERT INTO cart_added
                (user_id, item_name, unit_price, quantity, line_total, cart_key)
             VALUES
                (:user_id, :item_name, :unit_price, :quantity, :line_total, :cart_key)
             ON DUPLICATE KEY UPDATE
                quantity = LEAST(20, quantity + VALUES(quantity)),
                line_total = unit_price * quantity'
        );
        $statement->execute([
            'user_id' => $userId,
            'item_name' => $name,
            'unit_price' => $price,
            'quantity' => $quantity,
            'line_total' => $price * $quantity,
            'cart_key' => $cartKey,
        ]);
    } elseif ($action === 'quantity') {
        $quantity = $request['quantity'] ?? null;
        if (!is_int($quantity) || $quantity < 1 || $quantity > 20) {
            respond_cart(422, ['error' => 'Choose a quantity between 1 and 20.']);
        }
        $statement = $pdo->prepare(
            'UPDATE cart_added
             SET quantity = :quantity, line_total = unit_price * :line_quantity
             WHERE user_id = :user_id AND item_name = :name AND order_id IS NULL'
        );
        $statement->execute([
            'quantity' => $quantity,
            'line_quantity' => $quantity,
            'user_id' => $userId,
            'name' => $name,
        ]);
    } elseif ($action === 'remove') {
        $statement = $pdo->prepare(
            'DELETE FROM cart_added
             WHERE user_id = :user_id AND item_name = :name AND order_id IS NULL'
        );
        $statement->execute(['user_id' => $userId, 'name' => $name]);
    } else {
        respond_cart(422, ['error' => 'Unknown cart action.']);
    }

    $statement = $pdo->prepare(
        'SELECT id, item_name AS name, unit_price AS price, quantity
         FROM cart_added
         WHERE user_id = :user_id AND order_id IS NULL
         ORDER BY added_at, id'
    );
    $statement->execute(['user_id' => $userId]);
    respond_cart(200, ['items' => $statement->fetchAll()]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    respond_cart(500, ['error' => 'We could not update your cart. Please try again later.']);
}
