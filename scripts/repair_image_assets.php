<?php
/**
 * Repair brand PNG assets, ensure placeholder exists, and audit registered images.
 * Run: php scripts/repair_image_assets.php
 */
declare(strict_types=1);

require __DIR__ . '/../includes/AppConfig.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/images.php';

$base = imagesBaseDir();

ensureImagePlaceholder();
if (resolveImageRelativePath(imagePlaceholderRelativePath())) {
    echo 'OK  ' . imagePlaceholderRelativePath() . "\n";
}

ensureBrandImageAssets();
foreach (['logos/logo.jpg', 'icons/favicon.jpg', 'logos/logo.png', 'icons/favicon.png'] as $rel) {
    if (resolveImageRelativePath($rel) !== null) {
        echo "OK  $rel\n";
    }
}

$src = file_get_contents(__DIR__ . '/../includes/images.php');
preg_match_all("/img\\('([^']+)'\\)/", $src, $matches);
$missing = [];
$resolved = 0;

foreach (array_unique($matches[1]) as $rel) {
    if (resolveImageRelativePath($rel) !== null) {
        $resolved++;
        continue;
    }
    $missing[] = $rel;
}

echo "\nAudit: {$resolved} registered paths resolve locally\n";
if ($missing) {
    echo 'WARN missing (placeholder used at runtime): ' . count($missing) . "\n";
    foreach ($missing as $m) {
        echo "  - $m\n";
    }
    exit(1);
}

echo "All registered image paths resolve successfully.\n";
