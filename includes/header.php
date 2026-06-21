<?php
// includes/header.php
// Bagian atas layout: <head>, navbar. Di-include di setiap halaman
// setelah bootstrap.php di-load.

if (!isset($db)) {
    die('header.php tidak boleh diakses langsung. Pastikan bootstrap.php sudah di-load.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<?php if (Auth::isLoggedIn()): ?>
<nav class="navbar">
    <div class="brand"><?= e(APP_NAME) ?></div>
    <div class="nav-links">
        <span>
            <?= e($_SESSION['nama_lengkap']) ?>
            <span class="badge badge-<?= e($_SESSION['role']) ?>"><?= e($_SESSION['role']) ?></span>
        </span>
        <a href="<?= BASE_URL ?>/modules/auth/logout.php">Logout</a>
    </div>
</nav>
<?php endif; ?>

<div class="container">
    <?php $flash = getFlash(); ?>
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>
