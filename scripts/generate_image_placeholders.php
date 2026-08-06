<?php
/**
 * One-time script: creates placeholder images under assets/images/.
 * Run: php scripts/generate_image_placeholders.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$base = $root . '/assets/images';

$dirs = ['hero', 'bins', 'collectors', 'trucks', 'residents', 'gallery', 'services', 'team', 'logos', 'icons'];
foreach ($dirs as $dir) {
    $path = $base . '/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

function placeholderJpg(string $path, int $w, int $h, string $label, array $rgb = [226, 232, 240]): void
{
    $im = imagecreatetruecolor($w, $h);
    $bg = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
    imagefilledrectangle($im, 0, 0, $w, $h, $bg);

    $accent = imagecolorallocate($im, 16, 185, 129);
    imagefilledrectangle($im, 0, 0, $w, 6, $accent);

    $dark = imagecolorallocate($im, 51, 65, 85);
    $muted = imagecolorallocate($im, 100, 116, 139);

    $filename = basename($path);
    imagestring($im, 5, 20, (int) ($h / 2) - 24, 'SmartWaste Placeholder', $dark);
    imagestring($im, 4, 20, (int) ($h / 2), $filename, $accent);
    imagestring($im, 3, 20, (int) ($h / 2) + 20, $label, $muted);
    imagestring($im, 2, 20, $h - 28, "{$w} x {$h} — replace with your photo", $muted);

    imagejpeg($im, $path, 88);
    imagedestroy($im);
    echo "Created {$path}\n";
}

function placeholderBinPng(string $path, int $w, int $h, string $label, array $rgb): void
{
    $im = imagecreatetruecolor($w, $h);
    imagesavealpha($im, true);
    $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
    imagefill($im, 0, 0, $transparent);

    $body = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
    $dark = imagecolorallocate($im, (int) ($rgb[0] * 0.65), (int) ($rgb[1] * 0.65), (int) ($rgb[2] * 0.65));
    $wheel = imagecolorallocate($im, 30, 30, 30);

    $bodyW = (int) ($w * 0.55);
    $bodyH = (int) ($h * 0.72);
    $x = (int) (($w - $bodyW) / 2);
    $y = (int) ($h * 0.12);

    imagefilledrectangle($im, $x, $y, $x + $bodyW, $y + $bodyH, $body);
    imagefilledrectangle($im, $x - 4, $y - 10, $x + $bodyW + 4, $y, $dark);

    imagefilledellipse($im, $x + 14, $y + $bodyH + 8, 18, 18, $wheel);
    imagefilledellipse($im, $x + $bodyW - 14, $y + $bodyH + 8, 18, 18, $wheel);

    $text = imagecolorallocate($im, 255, 255, 255);
    imagestring($im, 3, $x + 8, $y + (int) ($bodyH / 2), $label, $text);

    imagepng($im, $path, 8);
    imagedestroy($im);
    echo "Created {$path}\n";
}

function placeholderLogoPng(string $path, int $size): void
{
    $im = imagecreatetruecolor($size, $size);
    imagesavealpha($im, true);
    $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
    imagefill($im, 0, 0, $transparent);

    $green = imagecolorallocate($im, 16, 185, 129);
    $white = imagecolorallocate($im, 255, 255, 255);
    imagefilledellipse($im, (int) ($size / 2), (int) ($size / 2), $size - 8, $size - 8, $green);
    imagestring($im, 5, (int) ($size / 2) - 28, (int) ($size / 2) - 8, 'SW', $white);

    imagepng($im, $path, 8);
    imagedestroy($im);
    echo "Created {$path}\n";
}

$photos = [
    'hero/hero-banner.jpg' => [1400, 880, 'Hero — clean Ghanaian neighbourhood'],
    'hero/hero-truck.jpg' => [800, 600, 'Hero collage — collection truck'],
    'hero/hero-collector.jpg' => [800, 600, 'Hero collage — uniformed collector'],
    'hero/hero-resident.jpg' => [800, 600, 'Hero collage — resident at home'],
    'hero/cta-background.jpg' => [1600, 900, 'Final CTA background'],

    'collectors/collector-with-resident.jpg' => [1200, 900, 'Officer handing bin to resident'],
    'collectors/collector-greeting-resident.jpg' => [900, 675, 'Collector greeting resident'],
    'collectors/collector-at-home.jpg' => [900, 675, 'Collector with bin at home'],

    'trucks/garbage-truck-1.jpg' => [1000, 750, 'Garbage collection truck'],
    'trucks/garbage-truck-2.jpg' => [1000, 750, 'Fleet truck — alternate angle'],

    'residents/ghana-family.jpg' => [800, 600, 'Ghanaian family / residents'],
    'residents/resident-using-app.jpg' => [900, 675, 'Resident using phone or computer'],
    'residents/happy-customers.jpg' => [900, 675, 'Happy customers after collection'],
    'residents/clean-neighbourhood.jpg' => [800, 600, 'Clean neighbourhood street'],

    'services/scheduling.jpg' => [700, 525, 'Scheduling & calendar'],
    'services/inventory.jpg' => [700, 525, 'Warehouse inventory'],
    'services/mobile-payments.jpg' => [700, 525, 'Mobile Money payments'],
    'services/tracking.jpg' => [700, 525, 'GPS collection tracking'],
    'services/notifications.jpg' => [700, 525, 'SMS & app notifications'],
    'services/reports.jpg' => [700, 525, 'Reports & analytics'],
    'services/route-optimisation.jpg' => [700, 525, 'Route optimisation map'],
    'services/recycling.jpg' => [700, 525, 'Recycling & sorting'],
    'services/waste-collection.jpg' => [900, 675, 'Waste collection in progress'],

    'team/team-uniforms.jpg' => [1000, 750, 'Staff in safety uniforms'],
    'team/testimonial-kwame.jpg' => [300, 300, 'Kwame Asante — testimonial photo'],
    'team/testimonial-ama.jpg' => [300, 300, 'Ama Serwaa — testimonial photo'],
    'team/testimonial-emmanuel.jpg' => [300, 300, 'Emmanuel Mensah — testimonial photo'],
];

for ($i = 1; $i <= 8; $i++) {
    $photos["gallery/gallery-{$i}.jpg"] = [700, 525, "Gallery image {$i}"];
}

foreach ($photos as $rel => [$w, $h, $label]) {
    placeholderJpg($base . '/' . $rel, $w, $h, $label);
}

placeholderBinPng($base . '/bins/small-bin.png', 280, 360, '120L', [34, 197, 94]);
placeholderBinPng($base . '/bins/medium-bin.png', 320, 400, '240L', [59, 130, 246]);
placeholderBinPng($base . '/bins/large-bin.png', 360, 440, '360L', [30, 30, 30]);

placeholderLogoPng($base . '/logos/logo.png', 200);
placeholderLogoPng($base . '/icons/favicon.png', 64);

echo "\nDone. Replace files in assets/images/ keeping the same names.\n";
