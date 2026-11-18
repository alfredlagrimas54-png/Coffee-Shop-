-- Apply once to an existing Flooded Coffee Shop database.
-- Do not promote an account until you have identified its email address.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer'
    AFTER password_hash;

CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(60) NOT NULL,
    price DECIMAL(8,2) UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL DEFAULT 'images/1.png',
    recipe_ingredients TEXT DEFAULT NULL,
    recipe_instructions TEXT DEFAULT NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_name (name),
    KEY idx_products_available_category (is_available, category, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO products
    (name, category, price, image_path, recipe_ingredients, recipe_instructions)
VALUES
    ('Classic Americano', 'coffee', 120.00, 'images/2.png', 'Espresso, hot water', 'Pull one shot of espresso and top with hot water.'),
    ('Caramel Macchiato', 'coffee', 155.00, 'images/3.png', 'Espresso, steamed milk, milk foam, caramel sauce', 'Steam the milk, add foam, pour in the espresso, and finish with caramel sauce.'),
    ('Vanilla Cold Brew', 'coffee', 150.00, 'images/1.png', 'Cold brew coffee, vanilla syrup, milk, ice', 'Combine vanilla syrup with cold brew, pour over ice, and add milk.'),
    ('Hazelnut Latte', 'coffee', 150.00, 'images/4.png', 'Espresso, steamed milk, hazelnut syrup', 'Mix hazelnut syrup with espresso, then add steamed milk.'),
    ('Dark Mocha', 'coffee', 160.00, 'images/5.png', 'Espresso, dark chocolate sauce, steamed milk', 'Stir chocolate sauce into espresso and add steamed milk.'),
    ('Spanish Latte', 'coffee', 145.00, 'images/6.png', 'Espresso, condensed milk, steamed milk', 'Combine condensed milk and espresso, then top with steamed milk.'),
    ('Iced Caramel Latte', 'iced-drinks', 150.00, 'images/7.png', 'Espresso, milk, caramel sauce, ice', 'Add caramel and milk to ice, pour in espresso, and stir.'),
    ('Iced Vanilla Coffee', 'iced-drinks', 145.00, 'images/8.png', 'Cold coffee, milk, vanilla syrup, ice', 'Combine vanilla syrup and coffee, pour over ice, and add milk.'),
    ('Iced Chocolate Mocha', 'iced-drinks', 155.00, 'images/9.png', 'Cold coffee, chocolate sauce, milk, ice', 'Stir chocolate sauce into coffee, pour over ice, and add milk.'),
    ('Brown Sugar Iced Latte', 'iced-drinks', 150.00, 'images/10.png', 'Espresso, brown sugar syrup, milk, ice', 'Mix brown sugar syrup with espresso, pour over ice, and add milk.'),
    ('Cold Brew Cream', 'iced-drinks', 155.00, 'images/11.png', 'Cold brew coffee, cream, ice', 'Pour cold brew over ice and top with cream.'),
    ('Iced Coconut Latte', 'iced-drinks', 160.00, 'images/12.png', 'Espresso, coconut milk, ice', 'Pour espresso and coconut milk over ice, then stir.'),
    ('Matcha Latte', 'non-coffee', 145.00, 'images/13.png', 'Matcha powder, hot water, steamed milk', 'Whisk matcha with hot water, then add steamed milk.'),
    ('Iced Matcha Cream', 'non-coffee', 155.00, 'images/14.png', 'Matcha powder, water, milk, ice, cream', 'Whisk matcha with water, pour over ice and milk, then top with cream.'),
    ('Chocolate Milk', 'non-coffee', 130.00, 'images/15.png', 'Milk, chocolate syrup, ice', 'Stir chocolate syrup into milk and serve over ice.'),
    ('Strawberry Milk', 'non-coffee', 140.00, 'images/16.png', 'Milk, strawberry syrup, ice', 'Mix strawberry syrup with milk and serve over ice.'),
    ('Honey Lemon Tea', 'non-coffee', 125.00, 'images/17.png', 'Black tea, lemon, honey, hot water', 'Steep the tea, then stir in honey and lemon.'),
    ('Butter Croissant', 'pastries', 95.00, 'images/18.png', 'Croissant dough, butter', 'Shape the laminated dough, proof it, and bake until golden.'),
    ('Chocolate Croissant', 'pastries', 110.00, 'images/19.png', 'Croissant dough, chocolate', 'Wrap chocolate in laminated dough, proof it, and bake until golden.'),
    ('Cinnamon Roll', 'pastries', 105.00, 'images/20.png', 'Yeast dough, butter, cinnamon, sugar', 'Spread the filling on rolled dough, slice, proof, and bake.'),
    ('Blueberry Muffin', 'pastries', 95.00, 'images/21.png', 'Flour, blueberries, milk, eggs, sugar', 'Fold blueberries into the batter, portion into tins, and bake.'),
    ('Chocolate Chip Cookie', 'pastries', 75.00, 'images/22.png', 'Flour, butter, sugar, chocolate chips', 'Mix the dough, fold in chocolate chips, and bake until lightly golden.'),
    ('Banana Bread', 'pastries', 90.00, 'images/23.png', 'Bananas, flour, eggs, butter, sugar', 'Mix the batter, pour into a loaf pan, and bake until set.'),
    ('Ham & Cheese Sandwich', 'light-meals', 150.00, 'images/24.png', 'Bread, ham, cheese, butter', 'Layer ham and cheese between bread, butter the outside, and grill.'),
    ('Chicken Pesto Sandwich', 'light-meals', 175.00, 'images/25.png', 'Bread, chicken, pesto, cheese', 'Spread pesto on bread, add chicken and cheese, then grill.'),
    ('Grilled Cheese', 'light-meals', 140.00, 'images/26.png', 'Bread, cheese, butter', 'Butter the bread, add cheese, and grill until crisp and melted.'),
    ('Egg & Cheese Toast', 'light-meals', 130.00, 'images/27.png', 'Bread, egg, cheese, butter', 'Toast the bread, top with cooked egg and cheese, then melt.'),
    ('Avocado Toast', 'light-meals', 160.00, 'images/28.png', 'Bread, avocado, lemon, salt', 'Toast the bread, mash avocado with lemon and salt, then spread.');

-- After choosing a registered account, promote it manually in phpMyAdmin:
-- UPDATE users SET role = 'admin' WHERE email = 'your-registered-email@example.com';
