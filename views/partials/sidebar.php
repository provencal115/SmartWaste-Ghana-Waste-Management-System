<?php
$role = $user['role_name'] ?? '';
$nav = [
    'resident' => [
        ['icon' => 'fa-gauge-high', 'label' => 'Dashboard', 'route' => 'resident/dashboard'],
        ['icon' => 'fa-calendar-days', 'label' => 'Schedule', 'route' => 'resident/schedule'],
        ['icon' => 'fa-credit-card', 'label' => 'Payments', 'route' => 'resident/payments'],
        ['icon' => 'fa-message', 'label' => 'Feedback', 'route' => 'resident/feedback'],
        ['icon' => 'fa-bell', 'label' => 'Notifications', 'route' => 'resident/notifications'],
    ],
    'collector' => [
        ['icon' => 'fa-gauge-high', 'label' => 'Dashboard', 'route' => 'collector/dashboard'],
        ['icon' => 'fa-route', 'label' => 'Routes', 'route' => 'collector/routes'],
        ['icon' => 'fa-qrcode', 'label' => 'Scan Bin', 'route' => 'collector/scan'],
        ['icon' => 'fa-triangle-exclamation', 'label' => 'Reports', 'route' => 'collector/reports'],
    ],
    'inventory_manager' => [
        ['icon' => 'fa-gauge-high', 'label' => 'Dashboard', 'route' => 'inventory/dashboard'],
        ['icon' => 'fa-dumpster', 'label' => 'Bins', 'route' => 'inventory/bins'],
        ['icon' => 'fa-chart-column', 'label' => 'Reports', 'route' => 'inventory/reports'],
    ],
    'administrator' => [
        ['icon' => 'fa-gauge-high', 'label' => 'Dashboard', 'route' => 'admin/dashboard'],
        ['icon' => 'fa-users', 'label' => 'Users', 'route' => 'admin/users'],
        ['icon' => 'fa-map-location-dot', 'label' => 'Zones', 'route' => 'admin/routes'],
        ['icon' => 'fa-truck', 'label' => 'Trucks', 'route' => 'admin/trucks'],
        ['icon' => 'fa-comments', 'label' => 'Complaints', 'route' => 'admin/complaints'],
        ['icon' => 'fa-envelope', 'label' => 'Contact Messages', 'route' => 'admin/messages', 'badge' => 'contact_unread'],
        ['icon' => 'fa-robot', 'label' => 'AI Assistant', 'route' => 'admin/chatbot'],
        ['icon' => 'fa-comment-sms', 'label' => 'SMS History', 'route' => 'admin/sms'],
        ['icon' => 'fa-file-export', 'label' => 'Reports', 'route' => 'admin/reports'],
        ['icon' => 'fa-sliders', 'label' => 'Settings', 'route' => 'admin/settings'],
        ['icon' => 'fa-clock-rotate-left', 'label' => 'Audit Logs', 'route' => 'admin/logs'],
    ],
    'finance_manager' => [
        ['icon' => 'fa-gauge-high', 'label' => 'Dashboard', 'route' => 'finance/dashboard'],
        ['icon' => 'fa-money-bill-wave', 'label' => 'Payments', 'route' => 'finance/payments'],
        ['icon' => 'fa-tags', 'label' => 'Pricing', 'route' => 'finance/pricing'],
        ['icon' => 'fa-chart-line', 'label' => 'Reports', 'route' => 'finance/reports'],
    ],
];
$items = $nav[$role] ?? [];
$current = trim($_GET['url'] ?? '', '/');
$initials = strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1));
$roleLabel = $config['roles'][$role] ?? str_replace('_', ' ', $role);
$contactUnread = 0;
if ($role === 'administrator') {
    try { $contactUnread = ContactMessageModel::unreadCount(); } catch (Throwable $e) {}
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="<?= e(siteLogo()) ?>" alt="SmartWaste" class="sidebar-brand-logo" width="32" height="32" loading="lazy">
        <span>Smart<strong>Waste</strong></span>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Main Menu</div>
        <?php foreach ($items as $item): ?>
        <a class="nav-link <?= str_starts_with($current, $item['route']) ? 'active' : '' ?>" href="<?= baseUrl($item['route']) ?>">
            <i class="fa-solid <?= $item['icon'] ?>"></i>
            <span><?= e($item['label']) ?></span>
            <?php if (($item['badge'] ?? '') === 'contact_unread' && $contactUnread > 0): ?>
            <span class="sidebar-nav-badge"><?= $contactUnread > 9 ? '9+' : $contactUnread ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= e($initials) ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></div>
                <div class="sidebar-user-role"><?= e($roleLabel) ?></div>
            </div>
        </div>
        <a class="nav-link text-danger" href="<?= baseUrl('auth/logout') ?>" onclick="return confirmLogout(event)">
            <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
        </a>
    </div>
</aside>
