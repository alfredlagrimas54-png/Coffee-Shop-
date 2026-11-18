<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $ordersStatement = database_connection()->prepare(
        'SELECT id, status, total, created_at
         FROM orders
         WHERE user_id = :user_id
         ORDER BY created_at DESC
         LIMIT 20'
    );
    $ordersStatement->execute(['user_id' => (int) $_SESSION['user_id']]);
    $orders = $ordersStatement->fetchAll();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    $orders = [];
    $errorMessage = 'We could not load your order history. Please try again later.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account | Flooded Coffee Shop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php require __DIR__ . '/navigation.php'; ?>
    <main class="account-page">
        <h1>Welcome, <?= htmlspecialchars((string) $_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="account-intro">Your recent orders will appear here.</p>
        <?php if (isset($errorMessage)): ?>
            <p class="account-message account-error" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif (isset($_GET['status']) && $_GET['status'] === 'order-placed'): ?>
            <p class="account-message" role="status">Your order was placed successfully.</p>
        <?php endif; ?>
        <section class="order-history" aria-labelledby="order-history-title">
            <h2 id="order-history-title">Order History</h2>
            <?php if ($orders === []): ?>
                <p>You have not placed any orders yet. <a href="buy-now.php">Browse the menu</a>.</p>
            <?php else: ?>
                <div class="order-list">
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $itemsStatement = database_connection()->prepare(
                            'SELECT item_name, quantity, line_total
                             FROM cart_added
                             WHERE order_id = :order_id
                             ORDER BY id'
                        );
                        $itemsStatement->execute(['order_id' => (int) $order['id']]);
                        $orderItems = $itemsStatement->fetchAll();
                        ?>
                        <article class="order-card">
                            <div class="order-heading">
                                <strong>Order #<?= (int) $order['id'] ?></strong>
                                <span><?= htmlspecialchars(ucfirst($order['status']), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p><?= htmlspecialchars(date('M j, Y g:i A', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
                            <ul>
                                <?php foreach ($orderItems as $item): ?>
                                    <li><?= htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') ?> × <?= (int) $item['quantity'] ?> — ₱<?= number_format((float) $item['line_total'], 2) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <strong>Total: ₱<?= number_format((float) $order['total'], 2) ?></strong>
                            <a href="receipt.php?order_id=<?= (int) $order['id'] ?>">View receipt</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
