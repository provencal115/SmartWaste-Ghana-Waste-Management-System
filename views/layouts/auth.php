<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <?php require __DIR__ . '/../partials/theme_init.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($config['name']) ?> — Account</title>
    <link rel="icon" href="<?= e(siteFavicon()) ?>" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/premium.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/theme-dark.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/password-field.css') ?>" rel="stylesheet">
    <?php require __DIR__ . '/../partials/app_urls.php'; ?>
</head>
<body class="auth-bg">
<?php uiPageLoader(); ?>
    <?php if (in_array(trim($_GET['url'] ?? '', '/'), ['auth/register', 'auth/login'], true)): ?>
    <a href="<?= baseUrl('home') ?>" class="back-home" aria-label="Back to homepage">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Home
    </a>
    <?php endif; ?>
    <button type="button" class="theme-toggle-auth topbar-btn" id="themeToggleAuth" aria-label="Toggle theme" style="position:fixed;top:1rem;right:1rem;z-index:100">
        <i class="fa-solid fa-moon"></i>
    </button>
    <div class="auth-split-layout min-vh-100">
        <div class="auth-visual-panel d-none d-lg-flex">
            <?php
            $authRoute = trim($_GET['url'] ?? '', '/');
            $authVisualImage = in_array($authRoute, ['auth/register', 'auth/login'], true)
                ? registerPanelImage()
                : authPanelImage();
            ?>
            <img src="<?= e($authVisualImage) ?>" alt="SmartWaste Ghana waste collection and community service" class="auth-visual-img" loading="eager">
            <div class="auth-visual-caption">
                <img src="<?= e(siteLogo()) ?>" alt="SmartWaste logo" class="auth-visual-logo" width="48" height="48" loading="lazy">
                <h2>SmartWaste Ghana</h2>
                <p>Modern garbage collection, inventory management, and Mobile Money payments for every community.</p>
            </div>
        </div>
        <div class="auth-form-panel">
            <div class="container py-5 position-relative">
                <?= $content ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= asset('js/password-field.js') ?>"></script>
    <script src="<?= asset('js/main.js') ?>"></script>
    <?php if ($flash): ?>
    <script>Swal.fire({ icon: '<?= $flash['type'] === 'error' ? 'error' : 'success' ?>', title: '<?= addslashes($flash['message']) ?>' });</script>
    <?php endif; ?>
</body>
</html>
