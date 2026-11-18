<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
$pageTitle = isset($pageTitle) ? (string) $pageTitle : 'Admin | Flooded Coffee Shop';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= admin_escape($pageTitle) ?></title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-layout">
    <?php require __DIR__ . '/admin_sedebar.php'; ?>
    <main class="admin-main">
