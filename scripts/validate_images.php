<?php
declare(strict_types=1);

$base = dirname(__DIR__) . '/assets/images';
$check = [
    'logos/logo.png', 'icons/favicon.png',
    'collectors/collector-with-resident.jpg', 'hero/hero-banner.jpg',
    'ghana/family-neighbourhood.jpg', 'testimonials/testimonial-kwame.jpg',
];

foreach ($check as $f) {
    $p = $base . '/' . str_replace('/', DIRECTORY_SEPARATOR, $f);
    $info = @getimagesize($p);
    $size = is_file($p) ? filesize($p) : 0;
    echo $f . ': ' . ($info ? ($info['mime'] . ' ' . $info[0] . 'x' . $info[1]) : 'INVALID') . " ({$size} bytes)\n";
}
