<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

try {
    $connection = database_connection();
    $productCount = (int) $connection->query('SELECT COUNT(*) FROM products WHERE is_available = 1')->fetchColumn();
    $orderCount = (int) $connection->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $pendingCount = (int) $connection->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
    $revenue = (float) $connection->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status = 'completed'")->fetchColumn();
    $customerCount = (int) $connection->query('SELECT COUNT(*) FROM users')->fetchColumn();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $productCount = 0;
    $orderCount = $pendingCount = $customerCount = 0;
    $revenue = 0.0;
}
?>
<?php
$pageTitle = 'Admin Dashboard | Flooded Coffee Shop';
require __DIR__ . '/../includes/header.php';
?>
        <h1>Dashboard</h1>
        <section class="stats-grid" aria-label="Shop statistics">
            <article class="stat-card"><span>Products</span><strong><?= $productCount ?></strong></article>
            <article class="stat-card"><span>Orders</span><strong><?= $orderCount ?></strong></article>
            <article class="stat-card"><span>Pending</span><strong><?= $pendingCount ?></strong></article>
            <article class="stat-card"><span>Customers</span><strong><?= $customerCount ?></strong></article>
            <article class="stat-card"><span>Completed Revenue</span><strong>₱<?= number_format($revenue, 2) ?></strong></article>
        </section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
