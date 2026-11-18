# Flooded Coffee Shop

A PHP coffee shop website for browsing products and placing orders.

## Features

- Browse products and prices
- View recipes
- Create an account and place orders
- Use a shopping cart
- View, print, and track orders
- Manage products and orders as an admin
- Change shop name, email, and opening hours

## Requirements

- XAMPP
- PHP with PDO MySQL
- MySQL or MariaDB

## Installation

1. Copy this project folder to `xampp/htdocs`.
2. Open XAMPP and start **Apache** and **MySQL**.
3. Open phpMyAdmin.
4. Import `flooded_coffee_shop.sql`.
5. Select the `flooded_coffee_shop` database.
6. Import these files in order:
   - `database_migrations/20261118_admin_and_products.sql`
   - `database_migrations/20261118_persistent_settings.sql`
7. Update the database settings in `database.php` if needed.
8. Open `http://localhost/Coffee_Shop/`.

The product migration adds 28 starter products. It restores missing products
but does not change existing products.

## Create an Admin Account

1. Register a normal account on the website.
2. Open phpMyAdmin and select `flooded_coffee_shop`.
3. Replace the example email and run:

```sql
UPDATE users
SET role = 'admin'
WHERE email = 'your-email@example.com';
```

4. Sign in, then open `http://localhost/Coffee_Shop/admin/`.

## Security

The database settings in `database.php` are for local use only. Before
publishing the website, use secure database credentials and HTTPS.
