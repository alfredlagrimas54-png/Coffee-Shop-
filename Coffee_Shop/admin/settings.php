<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$message = '';
try {
    $settings = database_connection()->query(
        'SELECT shop_name, shop_email, opening_hours
         FROM shop_settings
         WHERE id = 1'
    )->fetch();
    if (!$settings) {
        throw new RuntimeException('Shop settings row is missing.');
    }
} catch (PDOException | RuntimeException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Settings could not be loaded.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $message = 'Your session expired. Reload the page and try again.';
    } else {
        $submittedSettings = [
            'shop_name' => trim((string) ($_POST['shop_name'] ?? '')),
            'shop_email' => trim((string) ($_POST['shop_email'] ?? '')),
            'opening_hours' => trim((string) ($_POST['opening_hours'] ?? '')),
        ];
        $settings = $submittedSettings;

        if ($submittedSettings['shop_name'] === '' || strlen($submittedSettings['shop_name']) > 120) {
            $message = 'Enter a shop name (up to 120 characters).';
        } elseif (strlen($submittedSettings['shop_email']) > 254
            || ($submittedSettings['shop_email'] !== '' && !filter_var($submittedSettings['shop_email'], FILTER_VALIDATE_EMAIL))) {
            $message = 'Enter a valid shop email address.';
        } elseif ($submittedSettings['opening_hours'] === '' || strlen($submittedSettings['opening_hours']) > 120) {
            $message = 'Enter opening hours (up to 120 characters).';
        } else {
            try {
                $statement = database_connection()->prepare(
                    'INSERT INTO shop_settings (id, shop_name, shop_email, opening_hours)
                     VALUES (1, :shop_name, :shop_email, :opening_hours)
                     ON DUPLICATE KEY UPDATE
                         shop_name = VALUES(shop_name),
                         shop_email = VALUES(shop_email),
                         opening_hours = VALUES(opening_hours)'
                );
                $statement->execute($submittedSettings);
                $message = 'Settings saved.';
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                http_response_code(500);
                $message = 'Settings could not be saved.';
            }
        }
    }
}
?>
<?php
$pageTitle = 'Settings | Admin';
require __DIR__ . '/../includes/header.php';
?>
        <h1>Settings</h1>
        <?php if ($message !== ''): ?><p class="admin-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <form class="admin-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <label>Shop name
                <input type="text" name="shop_name" maxlength="120" required value="<?= htmlspecialchars($settings['shop_name'], ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label>Shop email
                <input type="email" name="shop_email" maxlength="254" value="<?= htmlspecialchars($settings['shop_email'], ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label>Opening hours
                <input type="text" name="opening_hours" maxlength="120" required value="<?= htmlspecialchars($settings['opening_hours'], ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <button type="submit">Save Settings</button>
        </form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
