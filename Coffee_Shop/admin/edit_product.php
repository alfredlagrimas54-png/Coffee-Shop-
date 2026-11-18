<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$productId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$message = '';

try {
    $statement = database_connection()->prepare('SELECT * FROM products WHERE id = :id');
    $statement->execute(['id' => $productId ?: 0]);
    $product = $statement->fetch();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Product could not be loaded.');
}

if (!$product) {
    http_response_code(404);
    $message = 'Product not found.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $message = 'Your session expired. Reload the page and try again.';
    } else {
        $newName = trim((string) ($_POST['name'] ?? ''));
        $category = (string) ($_POST['category'] ?? '');
        $priceInput = $_POST['price'] ?? '';
        $newPrice = is_numeric($priceInput) ? (float) $priceInput : 0.0;
        $imagePath = (string) ($_POST['image_path'] ?? '');
        $ingredients = trim((string) ($_POST['recipe_ingredients'] ?? ''));
        $instructions = trim((string) ($_POST['recipe_instructions'] ?? ''));
        $isAvailable = isset($_POST['is_available']) ? 1 : 0;

        if ($newName === '' || strlen($newName) > 150) {
            $message = 'Enter a product name (up to 150 characters).';
        } elseif (!array_key_exists($category, product_categories())) {
            $message = 'Choose a valid product category.';
        } elseif ($newPrice <= 0 || $newPrice > 999999.99) {
            $message = 'Enter a valid product price.';
        } elseif (!preg_match('/\Aimages\/(?:[1-9]|1[0-9]|2[0-8])\.png\z/', $imagePath)) {
            $message = 'Choose a valid product image.';
        } else {
            try {
                $statement = database_connection()->prepare(
                    'UPDATE products
                     SET name = :name, category = :category, price = :price, image_path = :image_path,
                         recipe_ingredients = :ingredients, recipe_instructions = :instructions,
                         is_available = :is_available
                     WHERE id = :id'
                );
                $statement->execute([
                    'name' => $newName,
                    'category' => $category,
                    'price' => number_format($newPrice, 2, '.', ''),
                    'image_path' => $imagePath,
                    'ingredients' => $ingredients !== '' ? $ingredients : null,
                    'instructions' => $instructions !== '' ? $instructions : null,
                    'is_available' => $isAvailable,
                    'id' => (int) $product['id'],
                ]);
                header('Location: products.php');
                exit;
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $message = 'A product with that name already exists.';
                } else {
                    error_log($exception->getMessage());
                    http_response_code(500);
                    $message = 'The product could not be updated.';
                }
            }
        }
    }
}

$displayName = (string) ($_POST['name'] ?? ($product['name'] ?? ''));
$displayCategory = (string) ($_POST['category'] ?? ($product['category'] ?? ''));
$displayPrice = (string) ($_POST['price'] ?? ($product['price'] ?? ''));
$displayImage = (string) ($_POST['image_path'] ?? ($product['image_path'] ?? 'images/1.png'));
$displayIngredients = (string) ($_POST['recipe_ingredients'] ?? ($product['recipe_ingredients'] ?? ''));
$displayInstructions = (string) ($_POST['recipe_instructions'] ?? ($product['recipe_instructions'] ?? ''));
?>
<?php
$pageTitle = 'Edit Product | Admin';
require __DIR__ . '/../includes/header.php';
?>
        <h1>Edit Product</h1>
        <?php if ($message !== ''): ?><p class="admin-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <?php if ($product): ?>
        <form class="admin-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <label>Product name
                <input type="text" name="name" required value="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label>Category
                <select name="category" required>
                    <?php foreach (product_categories() as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $displayCategory === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Price
                <input type="number" name="price" min="0.01" step="0.01" required value="<?= htmlspecialchars($displayPrice, ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label>Image
                <select name="image_path" required>
                    <?php for ($imageNumber = 1; $imageNumber <= 28; $imageNumber++): ?>
                        <?php $imagePath = 'images/' . $imageNumber . '.png'; ?>
                        <option value="<?= $imagePath ?>" <?= $displayImage === $imagePath ? 'selected' : '' ?>><?= $imagePath ?></option>
                    <?php endfor; ?>
                </select>
            </label>
            <label>Recipe ingredients
                <textarea name="recipe_ingredients" rows="3"><?= htmlspecialchars($displayIngredients, ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>
            <label>Recipe instructions
                <textarea name="recipe_instructions" rows="4"><?= htmlspecialchars($displayInstructions, ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>
            <label>
                <input type="checkbox" name="is_available" value="1" <?= (int) ($product['is_available'] ?? 1) === 1 ? 'checked' : '' ?>>
                Available for sale
            </label>
            <button type="submit">Save Changes</button>
        </form>
        <?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
