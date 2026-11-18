<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/includes/functions.php';

$categoryLabels = product_categories();
try {
    $products = database_connection()->query(
        'SELECT name, category, price, image_path, recipe_ingredients, recipe_instructions
         FROM products
         WHERE is_available = 1
         ORDER BY category, id'
    )->fetchAll();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('The product catalog could not be loaded.');
}

$groupedProducts = [];
foreach ($products as $product) {
    if (isset($categoryLabels[$product['category']])) {
        $groupedProducts[$product['category']][] = $product;
    }
}

$galleryLabels = $categoryLabels;
$galleryLabels['iced-drinks'] = 'Iced Coffee';
$bestMenuNames = ['Matcha Latte', 'Chocolate Croissant', 'Chicken Pesto Sandwich', 'Hazelnut Latte'];
$productsByName = [];
foreach ($products as $product) {
    $productsByName[$product['name']] = $product;
}
?>
<section id="menu-gallery">
    <div class="menu-gallery">
        <div class="menu-gallery-title">
            <h3>Coffee Gallery</h3>
        </div>
        <?php foreach ($galleryLabels as $category => $label): ?>
            <?php if (!empty($groupedProducts[$category])): ?>
                <div class="gallery-category">
                    <div class="gallery-category-title"><h3><?= admin_escape($label) ?></h3></div>
                    <div class="menu-grid">
                        <?php foreach ($groupedProducts[$category] as $product): ?>
                            <div class="menu-item">
                                <img src="<?= admin_escape($product['image_path']) ?>" alt="<?= admin_escape($product['name']) ?>">
                                <h4><?= admin_escape($product['name']) ?></h4>
                                <?php if ($product['recipe_ingredients'] !== null || $product['recipe_instructions'] !== null): ?>
                                    <button
                                        class="recipe-button"
                                        type="button"
                                        data-recipe-title="<?= admin_escape($product['name']) ?>"
                                        data-recipe-ingredients="<?= admin_escape((string) $product['recipe_ingredients']) ?>"
                                        data-recipe-preparation="<?= admin_escape((string) $product['recipe_instructions']) ?>"
                                    >View Recipe</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>

<section id="menu-prices">
    <h2>Menu Prices</h2>
    <div class="best-menu">
        <h3>Our Best Menu</h3>
        <div class="best-menu-items">
            <?php foreach ($bestMenuNames as $name): ?>
                <?php if (isset($productsByName[$name])): ?>
                    <div class="best-menu-item">
                        <span><?= admin_escape($name) ?></span>
                        <span>₱<?= number_format((float) $productsByName[$name]['price'], 2) ?></span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="menu-container">
        <?php foreach ($categoryLabels as $category => $label): ?>
            <?php if (!empty($groupedProducts[$category])): ?>
                <div class="menu-category">
                    <h3><?= admin_escape($label) ?></h3>
                    <?php foreach ($groupedProducts[$category] as $product): ?>
                        <div class="menu-row">
                            <span><?= admin_escape($product['name']) ?></span>
                            <span>₱<?= number_format((float) $product['price'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
