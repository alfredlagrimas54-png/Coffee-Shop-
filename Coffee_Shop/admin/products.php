<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

try {
    $products = database_connection()->query(
        'SELECT id, name, category, price, is_available
         FROM products
         ORDER BY category, name'
    )->fetchAll();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Products could not be loaded.');
}
?>
<?php
$pageTitle = 'Products | Admin';
require __DIR__ . '/../includes/header.php';
?>
        <h1>Products</h1>
        <div class="admin-actions">
            <a class="admin-button" href="add_product.php">Add Product</a>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Availability</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= admin_escape($product['name']) ?></td>
                        <td><?= admin_escape(product_categories()[$product['category']] ?? $product['category']) ?></td>
                        <td>₱<?= number_format((float) $product['price'], 2) ?></td>
                        <td><?= (int) $product['is_available'] === 1 ? 'Available' : 'Unavailable' ?></td>
                        <td><a href="edit_product.php?id=<?= (int) $product['id'] ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
