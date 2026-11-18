<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$allowedStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $message = 'Your session expired. Reload the page and try again.';
    } else {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        if ($orderId > 0 && in_array($status, $allowedStatuses, true)) {
            try {
                $statement = database_connection()->prepare('UPDATE orders SET status = :status WHERE id = :id');
                $statement->execute(['status' => $status, 'id' => $orderId]);
                $message = 'Order status updated.';
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                $message = 'The order could not be updated.';
            }
        }
    }
}

$orders = [];
try {
    $orders = database_connection()->query(
        'SELECT o.id, o.status, o.total, o.created_at, u.name
         FROM orders o
         JOIN users u ON u.id = o.user_id
         ORDER BY o.created_at DESC'
    )->fetchAll();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
}
?>
<?php
$pageTitle = 'Orders | Admin';
require __DIR__ . '/../includes/header.php';
?>
        <h1>Orders</h1>
        <?php if ($message !== ''): ?><p class="admin-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= (int) $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>₱<?= number_format((float) $order['total'], 2) ?></td>
                        <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                <select name="status" onchange="this.form.submit()">
                                    <?php foreach ($allowedStatuses as $status): ?>
                                        <option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
