<?php
declare(strict_types=1);

$base = dirname(__DIR__) . '/assets/images';
$bad = [];
$ok = 0;

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base)) as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $ext = strtolower($file->getExtension());
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        continue;
    }
    $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
    $info = @getimagesize($file->getPathname());
    if (!$info) {
        $bad[] = $rel . ' (invalid image data)';
        continue;
    }
    if ($ext === 'png' && $info['mime'] !== 'image/png') {
        $bad[] = $rel . " (extension .png but content is {$info['mime']})";
        continue;
    }
    $ok++;
}

echo "Valid images: $ok\n";
echo "Problems: " . count($bad) . "\n";
foreach ($bad as $b) {
    echo "  - $b\n";
}
