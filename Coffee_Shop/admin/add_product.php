<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $message = 'Your session expired. Reload the page and try again.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $category = (string) ($_POST['category'] ?? '');
        $priceInput = $_POST['price'] ?? '';
        $price = is_numeric($priceInput) ? (float) $priceInput : 0.0;
        $imagePath = (string) ($_POST['image_path'] ?? '');
        $ingredients = trim((string) ($_POST['recipe_ingredients'] ?? ''));
        $instructions = trim((string) ($_POST['recipe_instructions'] ?? ''));

        if ($name === '' || strlen($name) > 150) {
            $message = 'Enter a product name (up to 150 characters).';
        } elseif (!array_key_exists($category, product_categories())) {
            $message = 'Choose a valid product category.';
        } elseif ($price <= 0 || $price > 999999.99) {
            $message = 'Enter a valid product price.';
        } elseif (!preg_match('/\Aimages\/(?:[1-9]|1[0-9]|2[0-8])\.png\z/', $imagePath)) {
            $message = 'Choose a valid product image.';
        } else {
            try {
                $statement = database_connection()->prepare(
                    'INSERT INTO products
                        (name, category, price, image_path, recipe_ingredients, recipe_instructions)
                     VALUES
                        (:name, :category, :price, :image_path, :ingredients, :instructions)'
                );
                $statement->execute([
                    'name' => $name,
                    'category' => $category,
                    'price' => number_format($price, 2, '.', ''),
                    'image_path' => $imagePath,
                    'ingredients' => $ingredients !== '' ? $ingredients : null,
                    'instructions' => $instructions !== '' ? $instructions : null,
                ]);
                header('Location: products.php');
                exit;
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $message = 'A product with that name already exists.';
                } else {
                    error_log($exception->getMessage());
                    http_response_code(500);
                    $message = 'The product could not be added.';
                }
            }
        }
    }
}
?>
<?php
$pageTitle = 'Add Product | Admin';
require __DIR__ . '/../includes/header.php';
?>
        <h1>Add Product</h1>
        <?php if ($message !== ''): ?><p class="admin-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <form class="admin-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <label>Product name
                <input type="text" name="name" required value="<?= htmlspecialchars((string) ($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label>Category
                <select name="category" required>
                    <option value="">Choose category</option>
                    <?php foreach (product_categories() as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= ($_POST['category'] ?? '') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Price
                <input type="number" name="price" min="0.01" step="0.01" required value="<?= htmlspecialchars((string) ($_POST['price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label>Image
                <select name="image_path" required>
                    <?php for ($imageNumber = 1; $imageNumber <= 28; $imageNumber++): ?>
                        <?php $imagePath = 'images/' . $imageNumber . '.png'; ?>
                        <option value="<?= $imagePath ?>" <?= ($_POST['image_path'] ?? 'images/1.png') === $imagePath ? 'selected' : '' ?>><?= $imagePath ?></option>
                    <?php endfor; ?>
                </select>
            </label>
            <label>Recipe ingredients
                <textarea name="recipe_ingredients" rows="3"><?= htmlspecialchars((string) ($_POST['recipe_ingredients'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>
            <label>Recipe instructions
                <textarea name="recipe_instructions" rows="4"><?= htmlspecialchars((string) ($_POST['recipe_instructions'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>
            <button type="submit">Add Product</button>
        </form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
