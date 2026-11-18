-- Flooded Coffee Shop database
-- Import database_migrations/20261118_admin_and_products.sql after this schema
-- to seed the product catalog, and database_migrations/20261118_persistent_settings.sql
-- to initialize persisted shop settings.

CREATE DATABASE IF NOT EXISTS `flooded_coffee_shop`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `flooded_coffee_shop`;

DROP VIEW IF EXISTS `purchase_history`;
DROP VIEW IF EXISTS `cart_activity`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(254) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `category` VARCHAR(60) NOT NULL,
    `price` DECIMAL(8,2) UNSIGNED NOT NULL,
    `image_path` VARCHAR(255) NOT NULL DEFAULT 'images/1.png',
    `recipe_ingredients` TEXT DEFAULT NULL,
    `recipe_instructions` TEXT DEFAULT NULL,
    `is_available` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_products_name` (`name`),
    KEY `idx_products_available_category` (`is_available`, `category`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shop_settings` (
    `id` TINYINT UNSIGNED NOT NULL,
    `shop_name` VARCHAR(120) NOT NULL,
    `shop_email` VARCHAR(254) NOT NULL DEFAULT '',
    `opening_hours` VARCHAR(120) NOT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `shop_settings` (`id`, `shop_name`, `shop_email`, `opening_hours`)
VALUES (1, 'Flooded Coffee Shop', '', '8:00 AM - 10:00 PM');

CREATE TABLE IF NOT EXISTS `orders` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `status` ENUM('pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled')
        NOT NULL DEFAULT 'pending',
    `total` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_orders_user_created` (`user_id`, `created_at`),
    CONSTRAINT `fk_orders_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cart_added` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `order_id` BIGINT UNSIGNED DEFAULT NULL,
    `item_name` VARCHAR(150) NOT NULL,
    `unit_price` DECIMAL(8,2) NOT NULL,
    `quantity` TINYINT UNSIGNED NOT NULL,
    `line_total` DECIMAL(10,2) NOT NULL,
    `cart_key` VARCHAR(64) DEFAULT NULL,
    `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cart_added_cart_key` (`cart_key`),
    KEY `idx_cart_added_user_cart` (`user_id`, `order_id`),
    KEY `idx_cart_added_order` (`order_id`),
    CONSTRAINT `fk_cart_added_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_cart_added_order`
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
