<?php
/**
 * Download high-quality stock images into assets/images/
 * Run: php scripts/download_site_images.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/images.php';

if (!function_exists('curl_init')) {
    fwrite(STDERR, "PHP cURL extension required.\n");
    exit(1);
}

$base = dirname(__DIR__) . '/assets/images';
$urls = stockImageDownloadUrls();
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
        CURLOPT_TIMEOUT => 120,
        CURLOPT_USERAGENT => 'SmartWaste-Ghana/1.0',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $success = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    $size = is_file($dest) ? filesize($dest) : 0;
    if ($success && $code === 200 && $size > 10000) {
        echo "OK  $rel ($size bytes)\n";
        $ok++;
        continue;
    }

    @unlink($dest);
    echo "FAIL $rel HTTP $code\n";
    $fail++;
}

// Prefer user Ghana handover photo for the dedicated collector slot only
$whatsapp = $base . '/gallery/WhatsApp Image 2026-07-28 at 4.17.19 PM.jpeg';
$handover = $base . '/collectors/collector-with-resident.jpg';
if (is_file($whatsapp) && filesize($whatsapp) > 50000) {
    if (!is_dir(dirname($handover))) {
        mkdir(dirname($handover), 0755, true);
    }
    copy($whatsapp, $handover);
    echo "OK  collectors/collector-with-resident.jpg (from local Ghana photo)\n";
    $ok++;
}

// Workflow Step 6 — prefer Ghana handover photo (happy resident + collector)
$workflowDir = $base . '/workflow';
if (!is_dir($workflowDir)) {
    mkdir($workflowDir, 0755, true);
}
$step6 = $workflowDir . '/step-6-service-complete.jpg';
if (is_file($handover) && filesize($handover) > 50000) {
    copy($handover, $step6);
    echo "OK  workflow/step-6-service-complete.jpg (from Ghana handover photo)\n";
    $ok++;
}

// Workflow Step 4 bin loading — prefer local Ghana collection activity when available
$step4bin = $workflowDir . '/step-4-bin-loading.jpg';
$ghanaCollection = $base . '/ghana/collection-activity.jpg';
if (is_file($ghanaCollection) && filesize($ghanaCollection) > 10000) {
    copy($ghanaCollection, $step4bin);
    echo "OK  workflow/step-4-bin-loading.jpg (from ghana/collection-activity)\n";
}

echo "\nDone: $ok succeeded, $fail failed.\n";

ensureBrandImageAssets();
echo "OK  brand logo + favicon assets\n";
