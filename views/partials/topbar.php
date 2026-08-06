<?php
$current = trim($_GET['url'] ?? '', '/');
$parts = array_filter(explode('/', $current));
$role = $user['role_name'] ?? '';
$notifCount = 0;
try { $notifCount = NotificationModel::unreadCount($user['id']); } catch (Throwable $e) {}
$contactUnread = 0;
if ($role === 'administrator') {
    try { $contactUnread = ContactMessageModel::unreadCount(); } catch (Throwable $e) {}
}
$notifRoute = notificationRoute($role);
$initials = strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1));
$dashRoute = ($config['dashboard_routes'][$role] ?? 'home');
?>
<header class="topbar">
    <div class="topbar-left">
        <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="topbar-search d-none d-md-flex">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" placeholder="Search..." id="globalSearch" aria-label="Global search">
        </div>
    </div>
    <div class="topbar-right">
        <button type="button" class="topbar-btn" id="themeToggle" aria-label="Toggle dark mode">
            <i class="fa-solid fa-moon theme-icon-dark"></i>
            <i class="fa-solid fa-sun theme-icon-light d-none"></i>
        </button>
        <a href="<?= baseUrl($notifRoute) ?>" class="topbar-btn" title="Notifications">
            <i class="fa-solid fa-bell"></i>
            <?php if ($notifCount > 0): ?><span class="topbar-badge"><?= $notifCount > 9 ? '9+' : $notifCount ?></span><?php endif; ?>
        </a>
        <?php if ($role === 'administrator'): ?>
        <a href="<?= baseUrl('admin/messages') ?>" class="topbar-btn" title="Contact Messages">
            <i class="fa-solid fa-envelope"></i>
            <?php if ($contactUnread > 0): ?><span class="topbar-badge"><?= $contactUnread > 9 ? '9+' : $contactUnread ?></span><?php endif; ?>
        </a>
        <?php endif; ?>
        <div class="dropdown profile-dropdown">
            <button class="dropdown-toggle border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="sidebar-avatar" style="width:32px;height:32px;font-size:0.75rem"><?= e($initials) ?></div>
                <span class="d-none d-md-inline"><?= e($user['first_name']) ?></span>
                <span class="d-none d-lg-inline badge rounded-pill ms-1" style="background:var(--color-primary-subtle);color:var(--color-primary);font-size:0.65rem;font-weight:600"><?= e($config['roles'][$role] ?? $role) ?></span>
                <i class="fa-solid fa-chevron-down ms-1 small text-muted"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text small text-muted"><?= e($user['email']) ?></span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= baseUrl($dashRoute) ?>"><i class="fa-solid fa-gauge-high me-2"></i>Dashboard</a></li>
                <?php if ($role === 'resident'): ?>
                <li><a class="dropdown-item" href="<?= baseUrl('resident/notifications') ?>"><i class="fa-solid fa-bell me-2"></i>Notifications</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= baseUrl('auth/logout') ?>" onclick="return confirmLogout(event)"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</header>
