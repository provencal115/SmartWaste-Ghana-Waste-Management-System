<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <?php require __DIR__ . '/../partials/theme_init.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($config['name']) ?> — Dashboard</title>
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
<body class="dashboard-body">
<?php uiPageLoader(); ?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="app-wrapper">
    <?php require __DIR__ . '/../partials/sidebar.php'; ?>
    <div class="main-content">
        <?php require __DIR__ . '/../partials/topbar.php'; ?>
        <div class="page-content">
            <?php uiDashboardBanner(); ?>
            <?= $content ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= asset('js/password-field.js') ?>"></script>
<script src="<?= asset('js/main.js') ?>"></script>
<script src="<?= asset('js/charts.js') ?>"></script>
<?php if ($flash): ?>
<script>
Swal.fire({
    icon: '<?= $flash['type'] === 'error' ? 'error' : 'success' ?>',
    title: '<?= addslashes($flash['message']) ?>',
    toast: true, position: 'top-end', showConfirmButton: false, timer: 3500, timerProgressBar: true
});
</script>
<?php endif; ?>
</body>
</html>
