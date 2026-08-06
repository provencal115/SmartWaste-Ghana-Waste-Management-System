<?php
declare(strict_types=1);

require __DIR__ . '/../includes/AppConfig.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/images.php';

$base = dirname(__DIR__) . '/assets/images';
$minBytes = 1000;

function collectPaths(mixed $data, array &$out): void
{
    if (is_string($data)) {
        if (preg_match('#/assets/images/(.+)$#', $data, $m)) {
            $out[$m[1]] = true;
        }
        return;
    }
    if (is_array($data)) {
        foreach ($data as $v) {
            collectPaths($v, $out);
        }
    }
}

$paths = [];
collectPaths(landingImages(), $paths);
foreach (['logos/logo.png', 'icons/favicon.png'] as $p) {
    $paths[$p] = true;
}
foreach (array_keys(emptyStateImages()) as $k) {
    // URLs not paths - skip
}
$extras = [
    'login/auth-panel.jpg', 'register/register-panel.jpg', 'errors/error-404.jpg',
    'dashboard/admin-banner.jpg', 'dashboard/resident-banner.jpg',
    'dashboard/collector-banner.jpg', 'dashboard/finance-banner.jpg', 'dashboard/inventory-banner.jpg',
];
foreach ($extras as $p) {
    $paths[$p] = true;
}

// Parse images.php for img('...') paths
$src = file_get_contents(__DIR__ . '/../includes/images.php');
preg_match_all("/img\\('([^']+)'\\)/", $src, $m);
foreach ($m[1] as $p) {
    $paths[$p] = true;
}

$missing = [];
$small = [];
$ok = [];

foreach (array_keys($paths) as $rel) {
    $full = $base . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($full)) {
        $missing[] = $rel;
        continue;
    }
    $size = filesize($full);
    if ($size < $minBytes) {
        $small[] = "$rel ($size bytes)";
    } else {
        $ok[] = $rel;
    }
}

echo "OK: " . count($ok) . "\n";
echo "MISSING: " . count($missing) . "\n";
foreach ($missing as $m) {
    echo "  - $m\n";
}
echo "TOO SMALL (<{$minBytes}b): " . count($small) . "\n";
foreach ($small as $s) {
    echo "  - $s\n";
}
