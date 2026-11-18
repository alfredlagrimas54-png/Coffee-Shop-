<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';
?>
<header>
    <a class="brand" href="home.php" aria-label="Flooded Coffee Shop home">
        <img class="brand-logo" src="images/logo.png" alt="">
        <span class="brand-name">FLOODED</span>
    </a>

    <nav>
        <a href="home.php">Home</a>
        <a href="menu-gallery.php">Menu Gallery</a>
        <a href="menu-prices.php">Menu Prices</a>
        <a href="home.php#about">About</a>
        <a href="#contact">Contact</a>
        <a class="buy-now-nav" href="buy-now.php">Buy Now</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="account.php">Hi, <?= htmlspecialchars((string) $_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?></a>
            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                <a href="admin/dashboard.php">Admin</a>
            <?php endif; ?>
            <form class="nav-logout-form" method="post" action="logout.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit">Sign Out</button>
            </form>
        <?php else: ?>
            <a href="login.php?return=buy-now.php">Log In</a>
            <a href="signup.php?return=buy-now.php">Sign Up</a>
        <?php endif; ?>
    </nav>
</header>