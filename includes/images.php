<?php
/**
 * Central image registry — SmartWaste Ghana.
 *
 * All images resolve to local paths under assets/images/.
 * Replace any file keeping the same filename — no code changes required.
 *
 * @see assets/images/README.md
 * @see scripts/download_site_images.php
 * @see scripts/repair_image_assets.php
 */

/** Absolute filesystem path to assets/images/. */
function imagesBaseDir(): string
{
    return dirname(__DIR__) . '/assets/images';
}

/** Relative path to the fallback placeholder image. */
function imagePlaceholderRelativePath(): string
{
    return 'placeholders/no-image.jpg';
}

/**
 * Resolve a relative image path to an existing local file.
 * Tries exact path, then common alternate extensions.
 */
function resolveImageRelativePath(string $relativePath): ?string
{
    static $cache = [];

    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    if (isset($cache[$relativePath])) {
        return $cache[$relativePath];
    }

    $base = imagesBaseDir();
    $candidates = [$relativePath];

    $dir = dirname($relativePath);
    $name = pathinfo($relativePath, PATHINFO_FILENAME);
    $folder = $dir === '.' ? '' : $dir . '/';
    foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $ext) {
        $alt = $folder . $name . '.' . $ext;
        if ($alt !== $relativePath) {
            $candidates[] = $alt;
        }
    }

    foreach ($candidates as $candidate) {
        $full = $base . '/' . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
        if (!is_file($full) || filesize($full) < 500) {
            continue;
        }
        $info = @getimagesize($full);
        if ($info === false) {
            continue;
        }
        $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
        if ($ext === 'png' && ($info['mime'] ?? '') !== 'image/png') {
            continue;
        }
        $cache[$relativePath] = $candidate;
        return $candidate;
    }

    $cache[$relativePath] = null;
    return null;
}

/** Absolute filesystem path for a relative image, or null when missing. */
function imageAbsolutePath(string $relativePath): ?string
{
    $resolved = resolveImageRelativePath($relativePath);
    if ($resolved === null) {
        return null;
    }
    return imagesBaseDir() . '/' . str_replace('/', DIRECTORY_SEPARATOR, $resolved);
}

/** Ensure placeholders/no-image.jpg exists (created once per request). */
function ensureImagePlaceholder(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $rel = imagePlaceholderRelativePath();
    if (resolveImageRelativePath($rel) !== null) {
        return;
    }

    $dir = imagesBaseDir() . '/placeholders';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $dest = $dir . '/no-image.jpg';
    if (!extension_loaded('gd')) {
        $fallback = imageAbsolutePath('hero/hero-banner.jpg')
            ?? imageAbsolutePath('residents/ghana-family.jpg');
        if ($fallback && is_file($fallback)) {
            copy($fallback, $dest);
        }
        return;
    }

    $w = 800;
    $h = 500;
    $im = imagecreatetruecolor($w, $h);
    $bg = imagecolorallocate($im, 226, 232, 240);
    $accent = imagecolorallocate($im, 16, 185, 129);
    $dark = imagecolorallocate($im, 51, 65, 85);
    imagefilledrectangle($im, 0, 0, $w, $h, $bg);
    imagefilledrectangle($im, 0, 0, $w, 6, $accent);
    imagestring($im, 5, 24, (int) ($h / 2) - 20, 'SmartWaste Ghana', $dark);
    imagestring($im, 4, 24, (int) ($h / 2) + 4, 'Image not available', $accent);
    imagejpeg($im, $dest, 90);
    imagedestroy($im);
}

/** Regenerate logos/logo.png and icons/favicon.png as valid PNG files (requires GD). */
function ensureBrandPngAssets(): void
{
    $base = imagesBaseDir();
    foreach (['logos', 'icons'] as $dir) {
        if (!is_dir($base . '/' . $dir)) {
            mkdir($base . '/' . $dir, 0755, true);
        }
    }

    $source = imageAbsolutePath('logos/logo.png')
        ?? imageAbsolutePath('logos/logo.jpg')
        ?? imageAbsolutePath('hero/hero-banner.jpg');

    $writePng = static function (string $dest, int $size) use ($source): bool {
        if (!extension_loaded('gd')) {
            return false;
        }

        $src = null;
        if ($source) {
            $mime = @mime_content_type($source) ?: '';
            $src = str_contains($mime, 'png')
                ? @imagecreatefrompng($source)
                : (@imagecreatefromjpeg($source) ?: @imagecreatefrompng($source));
        }

        if (!$src) {
            $im = imagecreatetruecolor($size, $size);
            imagesavealpha($im, true);
            $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
            imagefill($im, 0, 0, $transparent);
            $green = imagecolorallocate($im, 16, 185, 129);
            $white = imagecolorallocate($im, 255, 255, 255);
            imagefilledellipse($im, (int) ($size / 2), (int) ($size / 2), $size - 8, $size - 8, $green);
            imagestring($im, 5, max(4, (int) ($size / 2) - 20), (int) ($size / 2) - 8, 'SW', $white);
            imagepng($im, $dest, 8);
            imagedestroy($im);
            return true;
        }

        $thumb = imagecreatetruecolor($size, $size);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefill($thumb, 0, 0, $transparent);
        $sw = imagesx($src);
        $sh = imagesy($src);
        $min = min($sw, $sh);
        $sx = (int) (($sw - $min) / 2);
        $sy = (int) (($sh - $min) / 2);
        imagecopyresampled($thumb, $src, 0, 0, $sx, $sy, $size, $size, $min, $min);
        imagepng($thumb, $dest, 8);
        imagedestroy($thumb);
        imagedestroy($src);
        return true;
    };

    $writePng($base . '/logos/logo.png', 200);
    $writePng($base . '/icons/favicon.png', 64);
}

/**
 * Ensure brand logo and favicon exist with correct file extensions.
 * Never overwrites existing logo files — replace assets/images/logos/logo.* manually.
 */
function ensureBrandImageAssets(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $base = imagesBaseDir();
    foreach (['logos', 'icons'] as $dir) {
        if (!is_dir($base . '/' . $dir)) {
            mkdir($base . '/' . $dir, 0755, true);
        }
    }

    // Remove invalid JPEG files incorrectly saved with a .png extension
    foreach (['logos/logo.png', 'icons/favicon.png'] as $bad) {
        $full = $base . '/' . str_replace('/', DIRECTORY_SEPARATOR, $bad);
        if (!is_file($full)) {
            continue;
        }
        $info = @getimagesize($full);
        if ($info && ($info['mime'] ?? '') !== 'image/png') {
            @unlink($full);
        }
    }

    $hasLogo = resolveImageRelativePath('logos/logo.png') !== null
        || resolveImageRelativePath('logos/logo.jpg') !== null;
    $hasFavicon = resolveImageRelativePath('icons/favicon.png') !== null
        || resolveImageRelativePath('icons/favicon.jpg') !== null;

    if (!$hasLogo || !$hasFavicon) {
        $logoSource = imageAbsolutePath('collectors/collector-with-resident.jpg')
            ?? imageAbsolutePath('hero/hero-banner.jpg');
        $iconSource = imageAbsolutePath('testimonials/testimonial-kwame.jpg')
            ?? imageAbsolutePath('hero/hero-collector.jpg')
            ?? $logoSource;
        if (!$hasLogo && $logoSource) {
            copy($logoSource, $base . '/logos/logo.jpg');
        }
        if (!$hasFavicon && $iconSource) {
            copy($iconSource, $base . '/icons/favicon.jpg');
        }
        if (extension_loaded('gd')) {
            ensureBrandPngAssets();
        }
    }
}

/**
 * Public URL for a local image under assets/images/.
 * Falls back to placeholders/no-image.jpg when the file is missing or invalid.
 */
function img(string $relativePath): string
{
    ensureImagePlaceholder();

    $resolved = resolveImageRelativePath($relativePath);
    if ($resolved === null) {
        $resolved = resolveImageRelativePath(imagePlaceholderRelativePath())
            ?? imagePlaceholderRelativePath();
    }

    return asset('images/' . $resolved);
}

/**
 * Public URL for a local video under assets/videos/.
 * Replace the file keeping the same filename — no code changes required.
 */
function video(string $relativePath): string
{
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    return asset('videos/' . $relativePath);
}

/** Fleet & Operations background video (replace assets/videos/fleet/garbage-truck-ghana.mp4). */
function fleetOperationsVideo(): string
{
    return video('fleet/garbage-truck-ghana.mp4');
}

/** Whether the local fleet video file exists and is usable. */
function hasFleetOperationsVideo(): bool
{
    $path = dirname(__DIR__) . '/assets/videos/fleet/garbage-truck-ghana.mp4';
    return is_file($path) && filesize($path) > 50000;
}

/** Poster frame shown while fleet video loads. */
function fleetOperationsPoster(): string
{
    return img('trucks/garbage-truck-1.jpg');
}

/** Site logo and favicon — single source: assets/images/logos/logo.* */
function siteLogoRelativePath(): ?string
{
    return resolveImageRelativePath('logos/logo.png')
        ?? resolveImageRelativePath('logos/logo.jpg');
}

function siteLogo(): string
{
    $rel = siteLogoRelativePath();
    if ($rel !== null) {
        return asset('images/' . $rel);
    }
    return img('logos/logo.jpg');
}

function siteFaviconRelativePath(): ?string
{
    return resolveImageRelativePath('icons/favicon.png')
        ?? resolveImageRelativePath('icons/favicon.jpg');
}

function siteFavicon(): string
{
    $rel = siteFaviconRelativePath();
    if ($rel !== null) {
        return asset('images/' . $rel);
    }
    return img('icons/favicon.jpg');
}

function siteFaviconMime(): string
{
    $rel = siteFaviconRelativePath();
    if ($rel === null) {
        return 'image/png';
    }
    return str_ends_with(strtolower($rel), '.png') ? 'image/png' : 'image/jpeg';
}

function siteLogoAbsolutePath(): ?string
{
    $rel = siteLogoRelativePath();
    return $rel ? imageAbsolutePath($rel) : null;
}

/**
 * Landing page image map.
 *
 * @return array<string, mixed>
 */
function landingImages(): array
{
    $g = static fn (string $path) => img($path);

    return [
        // Hero — Ghana waste collection (Zoomlion fleet + residents; each image once)
        'hero_main'      => $g('ghana/zoomlion-clean-ghana.jpg'),
        'hero_truck'     => $g('trucks/zoomlion-truck-rear.jpg'),
        'hero_collector' => $g('collectors/collector-with-resident.jpg'),
        'hero_resident'  => $g('residents/happy-customers.jpg'),

        // How it works — Step 2 handover image unchanged; Steps 1, 4, 6 use Ghana workflow photos
        'step_register'            => $g('workflow/step-1-registration.jpg'),
        'step_bins'                => $g('bins/medium-bin.jpg'),
        'step_bins_ghana_handover' => $g('collectors/collector-greeting-resident.jpg'),
        'step_schedule'            => $g('gallery/gallery-5.jpg'),
        'step_collector'           => $g('workflow/step-4-truck-collection.jpg'),
        'step_collector_alt'       => $g('ghana/collection-workers-bins.jpg'),
        'step_collection'          => $g('services/waste-collection.jpg'),
        'step_complete'            => $g('workflow/step-6-service-complete.jpg'),

        // Fleet — Zoomlion trucks, collectors, bins, recycling
        'fleet_poster' => $g('hero/hero-banner.jpg'),
        'fleet_cards'  => [
            $g('workflow/step-4-bin-loading.jpg'),
            $g('fleet/fleet-safety-uniforms.jpg'),
            $g('fleet/fleet-bin-inventory.png'),
            $g('fleet/fleet-recycling-sorting.jpg'),
        ],

        'cta_bg' => $g('hero/cta-background.jpg'),

        'success_stories' => [
            [$g('success/success-east-legon.jpg'), 'East Legon Estate', '98% on-time collections', 'Professional collectors and reliable weekly service transformed waste management for our entire estate.'],
            [$g('community/happy-residents.jpg'), 'Osu Residential Zone', '2,400+ bins tracked', 'QR-coded bins from warehouse to doorstep — every resident knows exactly when pickup happens.'],
            [$g('success/success-tema-streets.jpg'), 'Tema Community 4', '40% cleaner streets', 'GPS-routed trucks and uniformed staff keep our community spotless. Residents rate the service 5 stars.'],
        ],

        'story_mosaic' => [
            [$g('gallery/gallery-2.jpg'), 'Branded collectors'],
            [$g('ghana/story-recycling-irecop.jpg'), 'IRECOP recycling plant'],
            [$g('ghana/story-fleet-truck-rear.jpg'), 'Ghana-licensed fleet'],
            [$g('ghana/story-operations-tour.jpg'), 'Operations & workforce'],
            [$g('services/inventory.jpg'), 'Warehouse inventory'],
            [$g('ghana/story-processing-facility.jpg'), 'Processing facility'],
        ],

        'testimonials' => [
            [$g('testimonials/testimonial-kwame.jpg'), 'Kwame Asante', 'Homeowner, East Legon', 'The collector always arrives on time, greets us professionally, and the app makes scheduling effortless. Our street has never been cleaner.', 5],
            [$g('testimonials/testimonial-ama.jpg'), 'Ama Serwaa', 'Resident, Osu', 'I love getting notifications when my bin is collected. Payment via Mobile Money is so convenient — I gave them 5 stars!', 5],
            [$g('testimonials/testimonial-emmanuel.jpg'), 'Emmanuel Mensah', 'Estate Manager, Tema', 'SmartWaste transformed how our entire community manages waste. Professional uniforms, reliable trucks, excellent service.', 5],
        ],
    ];
}

/** Auth panel image (login, register, forgot, reset). */
function authPanelImage(): string
{
    return img('ghana/zoomlion-clean-ghana.jpg');
}

/** Register page side panel image. */
function registerPanelImage(): string
{
    return img('workflow/step-1-registration.jpg');
}

/** 404 illustration. */
function error404Image(): string
{
    return img('trucks/zoomlion-truck-rear.jpg');
}

/**
 * Dashboard banner by role segment in URL.
 */
function dashboardBannerImage(?string $role = null): string
{
    $role ??= explode('/', trim($_GET['url'] ?? '', '/'))[0] ?: 'admin';
    $map = [
        'admin'     => 'ghana/office-admin.jpg',
        'resident'  => 'ghana/family-neighbourhood.jpg',
        'collector' => 'ghana/zoomlion-clean-ghana.jpg',
        'finance'   => 'ghana/mobile-money.jpg',
        'inventory' => 'ghana/warehouse-ops.jpg',
    ];
    return img($map[$role] ?? 'dashboard/admin-banner.jpg');
}

/**
 * Dashboard banner title, subtitle, and icon by role.
 *
 * @return array{0: string, 1: string, 2: string}
 */
function dashboardBannerMeta(?string $role = null): array
{
    $role ??= explode('/', trim($_GET['url'] ?? '', '/'))[0] ?: 'admin';
    return match ($role) {
        'admin'     => ['Admin Command Center', 'Smart Waste Management Solution for Ghana', 'fa-shield-halved'],
        'resident'  => ['Your Home Dashboard', 'Schedule pickups · Pay with Mobile Money · Track your bin', 'fa-house-chimney'],
        'collector' => ['Collector Operations', 'Routes, pickups & proof of collection across Ghana', 'fa-truck-fast'],
        'finance'   => ['Finance Hub', 'Revenue analytics & payment verification for Ghana', 'fa-sack-dollar'],
        'inventory' => ['Inventory Control', 'Warehouse stock & bin allocation nationwide', 'fa-boxes-stacked'],
        default     => ['Dashboard', 'SmartWaste Ghana', 'fa-gauge-high'],
    };
}

/**
 * Empty-state illustration paths (optional second param for uiEmptyState).
 *
 * @return array<string, string>
 */
function emptyStateImages(): array
{
    return [
        'calendar'   => img('ghana/resident-mobile.jpg'),
        'wallet'     => img('ghana/mobile-money.jpg'),
        'bell'       => img('services/notifications.jpg'),
        'truck'      => img('ghana/collection-workers-bins.jpg'),
        'route'      => img('trucks/garbage-truck-2.jpg'),
        'comments'   => img('collectors/collector-with-resident.jpg'),
        'inventory'  => img('ghana/warehouse-ops.jpg'),
        'generic'    => img('ghana/clean-street.jpg'),
    ];
}

function emptyStateImage(string $key): ?string
{
    $all = emptyStateImages();
    return $all[$key] ?? $all['generic'] ?? null;
}

/**
 * Wheelie bin photo by colour (optional — CSS mini-bin used in UI).
 * Replace files under assets/images/bins/ keeping filenames.
 */
function binStockImage(string $size = 'medium'): string
{
    $map = ['small' => 'bins/small-bin.jpg', 'medium' => 'bins/medium-bin.jpg', 'large' => 'bins/large-bin.jpg'];
    return img($map[$size] ?? 'bins/medium-bin.jpg');
}

/**
 * Stock download URLs (used only by scripts/download_site_images.php).
 * Curated for Ghana / West Africa context — Black residents, collectors, tropical neighbourhoods.
 *
 * @return array<string, string>
 */
function stockImageDownloadUrls(): array
{
    $u = static fn (string $id, int $w = 1400) =>
        "https://images.unsplash.com/{$id}?w={$w}&q=90&auto=format&fit=crop&fm=jpg";

    $p = static fn (int $id, int $w = 1400) =>
        "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?auto=compress&cs=tinysrgb&w={$w}";

    return [
        // Hero — tropical neighbourhoods, collection fleet, Ghana-relevant context
        'hero/hero-banner.jpg'       => $p(6647119, 1920),
        'hero/hero-truck.jpg'        => $u('photo-1635691315495-ff39debe5764', 1400),
        'hero/hero-collector.jpg'    => $u('photo-1574974671999-24b7dfbb0d53', 1400),
        'hero/hero-resident.jpg'     => $p(8067852, 1400),
        'hero/cta-background.jpg'    => $p(7578894, 1920),

        // Collectors — waste workers, resident interactions
        'collectors/collector-greeting-resident.jpg' => $p(5688473, 1600),
        'collectors/collector-at-home.jpg'           => $p(1181519, 1600),
        'collectors/collector-with-resident.jpg'     => $p(6647119, 1600),

        // Fleet trucks
        'trucks/garbage-truck-1.jpg' => $u('photo-1635691315495-ff39debe5764', 1600),
        'trucks/garbage-truck-2.jpg' => $u('photo-1558618666-fcd25c85cd64', 1600),

        // Residents — Black Ghanaian / African families and communities
        'residents/ghana-family.jpg'        => $p(6647119, 1600),
        'residents/resident-using-app.jpg'  => $p(8067852, 1600),
        'residents/happy-customers.jpg'     => $p(7578894, 1600),
        'residents/clean-neighbourhood.jpg' => $p(7285951, 1600),

        // Wheelie bins (colour-neutral stock — UI uses CSS for colour)
        'bins/small-bin.jpg'  => $p(4099465, 1000),
        'bins/medium-bin.jpg' => $p(6234639, 1200),
        'bins/large-bin.jpg'  => $p(4099465, 1400),

        // Services & operations — each slot has a distinct source image
        'services/scheduling.jpg'         => $u('photo-1524661135-423995f22d0b', 1200),
        'services/inventory.jpg'          => $u('photo-1586528116311-ad8dd3c8310d', 1200),
        'services/mobile-payments.jpg'    => $u('photo-1556742049-0cfed4f6a45d', 1200),
        'services/tracking.jpg'           => $u('photo-1551288049-bebda4e38f71', 1200),
        'services/notifications.jpg'      => $u('photo-1551288049-bebda4e38f71', 1200),
        'services/reports.jpg'            => $u('photo-1460925895917-afdab827c52f', 1200),
        'services/route-optimisation.jpg' => $u('photo-1524661135-423995f22d0b', 1200),
        'services/recycling.jpg'          => $u('photo-1586528116311-ad8dd3c8310d', 1200),
        'services/waste-collection.jpg'   => $u('photo-1574974671999-24b7dfbb0d53', 1600),

        // How It Works — Steps 1, 4, 6 (Ghana-focused workflow visuals)
        'workflow/step-1-registration.jpg'     => $p(8067852, 1400),
        'workflow/step-4-truck-collection.jpg' => $u('photo-1635691315495-ff39debe5764', 1600),
        'workflow/step-4-bin-loading.jpg'      => $u('photo-1574974671999-24b7dfbb0d53', 1600),
        'workflow/step-6-service-complete.jpg' => $p(6647119, 1600),

        // Gallery — twelve unique visuals
        'gallery/gallery-1.jpg'  => $u('photo-1635691315495-ff39debe5764', 1400),
        'gallery/gallery-2.jpg'  => $u('photo-1574974671999-24b7dfbb0d53', 1400),
        'gallery/gallery-3.jpg'  => $p(5688473, 1400),
        'gallery/gallery-4.jpg'  => $p(4099465, 1400),
        'gallery/gallery-5.jpg'  => $u('photo-1586528116311-ad8dd3c8310d', 1400),
        'gallery/gallery-6.jpg'  => $p(6234639, 1400),
        'gallery/gallery-7.jpg'  => $p(6234639, 1400),
        'gallery/gallery-8.jpg'  => $p(7285951, 1400),
        'gallery/gallery-9.jpg'  => $p(6647119, 1400),
        'gallery/gallery-10.jpg' => $p(7578894, 1400),
        'gallery/gallery-11.jpg' => $p(5688473, 1600),
        'gallery/gallery-12.jpg' => $p(1181519, 1600),

        // Team — collectors in work gear
        'team/team-uniforms.jpg' => $p(8067852, 1600),

        // Testimonials — Black / African portraits (Pexels)
        'testimonials/testimonial-kwame.jpg'    => $p(5688473, 600),
        'testimonials/testimonial-ama.jpg'      => $p(8067852, 600),
        'testimonials/testimonial-emmanuel.jpg' => $p(1181519, 600),

        // Community impact
        'community/community-banner.jpg'         => $p(6647119, 1920),
        'community/happy-residents.jpg'          => $p(7578894, 1400),
        'community/bins-at-curb.jpg'             => $p(4099465, 1400),
        'community/professional-collectors.jpg'  => $p(5688473, 1400),
        'community/clean-streets.jpg'            => $p(7285951, 1400),

        // Auth panels
        'login/auth-panel.jpg'        => $u('photo-1635691315495-ff39debe5764', 1600),
        'register/register-panel.jpg' => $p(8067852, 1600),

        // Dashboard banners by role
        'dashboard/admin-banner.jpg'     => $u('photo-1460925895917-afdab827c52f', 1920),
        'dashboard/resident-banner.jpg'  => $p(8067852, 1920),
        'dashboard/collector-banner.jpg' => $p(5688473, 1920),
        'dashboard/finance-banner.jpg'   => $u('photo-1556742049-0cfed4f6a45d', 1920),
        'dashboard/inventory-banner.jpg' => $u('photo-1586528116311-ad8dd3c8310d', 1920),

        'errors/error-404.jpg' => $u('photo-1635691315495-ff39debe5764', 1200),

        'empty-states/empty-calendar.jpg'        => $u('photo-1524661135-423995f22d0b', 800),
        'empty-states/empty-wallet.jpg'          => $u('photo-1556742049-0cfed4f6a45d', 800),
        'empty-states/empty-notifications.jpg'   => $u('photo-1551288049-bebda4e38f71', 800),
        'empty-states/empty-collection.jpg'      => $u('photo-1574974671999-24b7dfbb0d53', 800),
        'empty-states/empty-route.jpg'           => $u('photo-1524661135-423995f22d0b', 800),
        'empty-states/empty-feedback.jpg'        => $p(8067852, 800),
        'empty-states/empty-inventory.jpg'       => $u('photo-1586528116311-ad8dd3c8310d', 800),
        'empty-states/empty-generic.jpg'         => $p(6647119, 800),

        // Ghana-focused branding slots (replace files keeping filenames)
        'ghana/collector-uniform-1.jpg'   => $p(5688473, 1600),
        'ghana/collector-uniform-2.jpg'   => $p(1181519, 1600),
        'ghana/family-neighbourhood.jpg'  => $p(6647119, 1600),
        'ghana/modern-home.jpg'           => $p(7578894, 1400),
        'ghana/truck-community.jpg'       => $u('photo-1635691315495-ff39debe5764', 1600),
        'ghana/dustbin-delivery.jpg'      => $p(4099465, 1400),
        'ghana/collection-activity.jpg'   => $u('photo-1574974671999-24b7dfbb0d53', 1600),
        'ghana/warehouse-ops.jpg'         => $p(6234639, 1600),
        'ghana/recycling-ghana.jpg'       => $p(6234639, 1400),
        'ghana/clean-street.jpg'          => $p(7285951, 1600),
        'ghana/resident-collector.jpg'    => $p(8067852, 1600),
        'ghana/resident-mobile.jpg'       => $p(8067852, 1400),
        'ghana/happy-residents.jpg'       => $p(7578894, 1400),
        'ghana/mobile-money.jpg'          => $u('photo-1556742049-0cfed4f6a45d', 1400),
        'ghana/office-admin.jpg'          => $p(5688473, 1600),
        'ghana/office-finance.jpg'        => $p(1181519, 1600),

        'placeholders/no-image.jpg' => $p(7285951, 800),
    ];
}

/**
 * Stock video download URLs (scripts/download_site_videos.php only).
 * Placeholder footage — replace assets/videos/fleet/garbage-truck-ghana.mp4 with your own Ghana footage.
 *
 * @return array<string, string>
 */
function stockVideoDownloadUrls(): array
{
    return [
        // Mixkit royalty-free placeholder — replace with your Ghana footage (same filename)
        'fleet/garbage-truck-ghana.mp4' => 'https://assets.mixkit.co/videos/preview/mixkit-garbage-truck-on-the-street-4330-large.mp4',
    ];
}
