CREATE TABLE IF NOT EXISTS shop_settings (
    id TINYINT UNSIGNED NOT NULL,
    shop_name VARCHAR(120) NOT NULL,
    shop_email VARCHAR(254) NOT NULL DEFAULT '',
    opening_hours VARCHAR(120) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO shop_settings (id, shop_name, shop_email, opening_hours)
VALUES (1, 'Flooded Coffee Shop', '', '8:00 AM - 10:00 PM');
