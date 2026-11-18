<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
$order = null;
$items = [];
$errorMessage = '';

if ($orderId === false || $orderId === null || $orderId < 1) {
    http_response_code(400);
    $errorMessage = 'Enter a valid order number to view the receipt.';
} else {
    try {
        $connection = database_connection();
        $orderStatement = $connection->prepare(
            'SELECT id, total, created_at
             FROM orders
             WHERE id = :order_id AND user_id = :user_id'
        );
        $orderStatement->execute([
            'order_id' => $orderId,
            'user_id' => (int) $_SESSION['user_id'],
        ]);
        $order = $orderStatement->fetch();

        if (!$order) {
            http_response_code(404);
            $errorMessage = 'We could not find that receipt.';
        } else {
            $itemsStatement = $connection->prepare(
                'SELECT item_name, unit_price, quantity, line_total
                 FROM cart_added
                 WHERE order_id = :order_id AND user_id = :user_id
                 ORDER BY id'
            );
            $itemsStatement->execute([
                'order_id' => $orderId,
                'user_id' => (int) $_SESSION['user_id'],
            ]);
            $items = $itemsStatement->fetchAll();
        }
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        http_response_code(500);
        $errorMessage = 'We could not load your receipt. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt | Flooded Coffee Shop</title>
    <link rel="stylesheet" href="style.css?v=receipt-1">
</head>
<body class="receipt-page">
    <?php require __DIR__ . '/navigation.php'; ?>
    <main class="receipt-content">
        <?php if ($errorMessage !== ''): ?>
            <section class="receipt-card">
                <h1>Receipt unavailable</h1>
                <p><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
                <p><a href="account.php">Return to your account</a></p>
            </section>
        <?php else: ?>
            <article class="receipt-card" aria-labelledby="receipt-title">
                <div class="receipt-heading">
                    <h1 id="receipt-title">Order Receipt</h1>
                    <strong>#<?= (int) $order['id'] ?></strong>
                </div>
                <div class="receipt-meta">
                    <span><?= htmlspecialchars((string) $_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <time datetime="<?= htmlspecialchars(date(DATE_ATOM, strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars(date('M j, Y g:i A', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                    </time>
                </div>
                <table class="receipt-items">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $item['quantity'] ?></td>
                                <td>₱<?= number_format((float) $item['unit_price'], 2) ?></td>
                                <td>₱<?= number_format((float) $item['line_total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="receipt-total">
                    <strong>Total paid</strong>
                    <strong>₱<?= number_format((float) $order['total'], 2) ?></strong>
                </div>
                <p class="receipt-thanks">Thank you for choosing Flooded Coffee Shop.</p>
                <div class="receipt-actions">
                    <button type="button" onclick="window.print()">Print receipt</button>
                    <a href="account.php">My account</a>
                </div>
            </article>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
