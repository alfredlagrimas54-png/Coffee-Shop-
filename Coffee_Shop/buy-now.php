<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?return=buy-now.php');
    exit;
}

$csrfToken = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flooded Coffee Shop | Buy Now</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body class="buy-page">
    <div id="site-navigation"></div>
    <header class="buy-hero">
        <div class="buy-hero-copy">
            <p class="buy-hero-kicker">FLOODED ORDER DESK</p>
            <h1>Build your coffee break.</h1>
            <p>Choose your favorites, set the quantity, and send your order to the cart.</p>
        </div>
        <div class="buy-hero-note">
            <span>OPEN TODAY</span>
            <strong>8:00 AM - 10:00 PM</strong>
        </div>
    </header>
    <main id="page-content" data-page="buy" data-csrf-token="<?= $csrfToken ?>"></main>
    <?php require __DIR__ . '/footer.php'; ?>
    <script src="script.js?v=menu-content-2"></script>
</body>
</html>
