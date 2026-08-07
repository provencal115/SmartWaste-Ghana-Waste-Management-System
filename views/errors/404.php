<?php
if (!function_exists('appConfig')) {
    require_once __DIR__ . '/../../includes/AppConfig.php';
}
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/images.php';
$config = appConfig();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <?php require __DIR__ . '/../partials/theme_init.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — <?= e($config['name']) ?></title>
    <link rel="icon" href="<?= e(siteFavicon()) ?>" type="<?= e(siteFaviconMime()) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/premium.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/theme-dark.css') ?>" rel="stylesheet">
</head>
<body class="auth-bg">
    <div class="min-vh-100 d-flex align-items-center justify-content-center">
        <div class="text-center animate-in px-3" style="max-width:520px">
            <img src="<?= e(error404Image()) ?>" alt="Page not found — SmartWaste Ghana" class="error-page-photo mb-4" loading="lazy" width="480" height="280">
            <h1 class="display-1 fw-bold text-gradient">404</h1>
            <p class="lead text-secondary mb-4">This page doesn't exist or has been moved.</p>
            <a href="<?= baseUrl('home') ?>" class="btn-saas btn-saas-primary"><i class="fa-solid fa-house"></i> Back to Home</a>
        </div>
    </div>
    <script src="<?= asset('js/main.js') ?>"></script>
</body>
</html>
