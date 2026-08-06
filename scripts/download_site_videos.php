<?php
/**
 * Download placeholder fleet video into assets/videos/
 * Run: php scripts/download_site_videos.php
 *
 * Replace assets/videos/fleet/garbage-truck-ghana.mp4 with your own Ghana footage anytime.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/images.php';

if (!function_exists('curl_init')) {
    fwrite(STDERR, "PHP cURL extension required.\n");
    exit(1);
}

$base = dirname(__DIR__) . '/assets/videos';
$urls = stockVideoDownloadUrls();
$ok = 0;
$fail = 0;

foreach ($urls as $rel => $url) {
    $dest = $base . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $dir = dirname($dest);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $fp = fopen($dest, 'wb');
    if (!$fp) {
        echo "FAIL (open): $rel\n";
        $fail++;
        continue;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 300,
        CURLOPT_USERAGENT => 'SmartWaste-Ghana/1.0',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => ['Accept: video/mp4,*/*'],
    ]);
    $success = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    $size = is_file($dest) ? filesize($dest) : 0;
    if ($success && $code === 200 && $size > 50000) {
        echo "OK  $rel (" . round($size / 1024 / 1024, 2) . " MB)\n";
        $ok++;
        continue;
    }

    @unlink($dest);
    echo "FAIL $rel HTTP $code\n";
    $fail++;
}

echo "\nDone: $ok succeeded, $fail failed.\n";
if ($fail) {
    echo "Tip: copy your Ghana fleet video to assets/videos/fleet/garbage-truck-ghana.mp4\n";
}
