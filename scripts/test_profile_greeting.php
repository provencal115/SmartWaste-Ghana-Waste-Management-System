<?php
require_once __DIR__ . '/../includes/AppConfig.php';
require_once __DIR__ . '/../includes/Model.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/ui.php';

foreach (glob(__DIR__ . '/../models/*.php') as $m) {
    require_once $m;
}

date_default_timezone_set('Africa/Accra');

$user = ['first_name' => 'Isaac', 'last_name' => 'Test', 'avatar_url' => ''];
$greeting = dashboardGreeting($user);
$hour = (int) date('G');
$expectedPeriod = $hour >= 5 && $hour < 12 ? 'Good Morning' : ($hour >= 12 && $hour < 17 ? 'Good Afternoon' : 'Good Evening');
$ok = str_starts_with($greeting, $expectedPeriod) && str_contains($greeting, 'Isaac');
echo ($ok ? 'PASS' : 'FAIL') . ": dashboardGreeting => {$greeting}\n";

$initials = userAvatarInitials($user);
echo ($initials === 'IT' ? 'PASS' : 'FAIL') . ": userAvatarInitials => {$initials}\n";

$cols = Model::fetchAll("SHOW COLUMNS FROM users LIKE 'avatar_url'");
echo (count($cols) ? 'PASS' : 'FAIL') . ": avatar_url column\n";

$_GET['url'] = 'admin/dashboard';
echo (isRoleDashboardPage() ? 'PASS' : 'FAIL') . ": isRoleDashboardPage admin/dashboard\n";
$_GET['url'] = 'admin/settings';
echo (!isRoleDashboardPage() ? 'PASS' : 'FAIL') . ": isRoleDashboardPage admin/settings\n";

ob_start();
uiUserAvatar($user, 'test', 32);
$html = ob_get_clean();
echo (str_contains($html, 'user-avatar-default') ? 'PASS' : 'FAIL') . ": default avatar HTML\n";
