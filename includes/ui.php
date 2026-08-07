<?php
/**
 * Premium UI Component Helpers — views only, no business logic
 */

function uiKpi(string $title, string|int|float $value, string $icon, string $variant = 'primary', ?string $subtitle = null, int $delay = 0): void
{
    $variants = [
        'primary' => 'kpi-primary', 'success' => 'kpi-success', 'warning' => 'kpi-warning',
        'danger' => 'kpi-danger', 'info' => 'kpi-info', 'purple' => 'kpi-purple',
    ];
    $cls = $variants[$variant] ?? 'kpi-primary';
    $delayStyle = $delay ? ' style="animation-delay:' . ($delay * 0.08) . 's"' : '';
    echo '<div class="col-sm-6 col-xl-3"><div class="kpi-card ' . $cls . ' animate-in"' . $delayStyle . '>';
    echo '<div class="kpi-card-inner glass-card">';
    echo '<div class="kpi-icon"><i class="fa-solid ' . e($icon) . '"></i></div>';
    echo '<div class="kpi-content"><span class="kpi-label">' . e($title) . '</span>';
    echo '<h3 class="kpi-value">' . (is_numeric($value) ? e((string)$value) : $value) . '</h3>';
    if ($subtitle) echo '<span class="kpi-sub">' . e($subtitle) . '</span>';
    echo '</div></div></div></div>';
}

function uiPageHeader(string $title, ?string $subtitle = null, ?string $actionHtml = null): void
{
    $current = trim($_GET['url'] ?? '', '/');
    $parts = explode('/', $current);
    echo '<div class="page-header animate-in mb-4">';
    echo '<nav aria-label="breadcrumb" class="breadcrumb-nav"><ol class="breadcrumb">';
    echo '<li class="breadcrumb-item"><a href="#"><i class="fa-solid fa-house-chimney"></i></a></li>';
    foreach ($parts as $i => $part) {
        $isLast = $i === count($parts) - 1;
        echo '<li class="breadcrumb-item' . ($isLast ? ' active' : '') . '">' . e(ucwords(str_replace('-', ' ', $part))) . '</li>';
    }
    echo '</ol></nav>';
    echo '<div class="d-flex flex-wrap align-items-center justify-content-between gap-3">';
    echo '<div><h1 class="page-title">' . e($title) . '</h1>';
    if ($subtitle) echo '<p class="page-subtitle">' . e($subtitle) . '</p>';
    echo '</div>';
    if ($actionHtml) echo '<div class="page-actions">' . $actionHtml . '</div>';
    echo '</div></div>';
}

function uiEmptyState(string $icon, string $title, string $message, ?string $actionHtml = null, ?string $imageKey = null): void
{
    echo '<div class="empty-state glass-card animate-in">';
    if ($imageKey && ($src = emptyStateImage($imageKey))) {
        echo '<img src="' . e($src) . '" alt="" class="empty-state-photo" loading="lazy" width="280" height="160">';
    }
    echo '<div class="empty-icon"><i class="fa-solid ' . e($icon) . '"></i></div>';
    echo '<h5>' . e($title) . '</h5><p>' . e($message) . '</p>';
    if ($actionHtml) echo $actionHtml;
    echo '</div>';
}

function uiDashboardBanner(?string $role = null): void
{
    [$title, $subtitle, $icon] = dashboardBannerMeta($role);
    $src = dashboardBannerImage($role);
    echo '<div class="dashboard-hero-banner animate-in mb-4">';
    echo '<img src="' . e($src) . '" alt="SmartWaste Ghana — ' . e($title) . '" class="dashboard-hero-banner-img" loading="lazy">';
    echo '<div class="dashboard-hero-banner-overlay"></div>';
    echo '<div class="dashboard-hero-banner-content">';
    echo '<span class="dashboard-hero-badge"><i class="fa-solid fa-location-dot me-1"></i> Ghana</span>';
    echo '<h2 class="dashboard-hero-title"><i class="fa-solid ' . e($icon) . ' me-2"></i>' . e($title) . '</h2>';
    echo '<p class="dashboard-hero-subtitle mb-0">' . e($subtitle) . '</p>';
    echo '</div></div>';
}

/** Landing page Ghana branding strip. */
function uiGhanaBrandBar(): void
{
    echo '<section class="ghana-brand-bar" aria-label="Smart Waste Management for Ghana">';
    echo '<div class="container"><div class="ghana-brand-bar-inner reveal">';
    echo '<div class="ghana-brand-mark" aria-hidden="true"><i class="fa-solid fa-recycle"></i></div>';
    echo '<div class="ghana-brand-text"><strong>Smart Waste Management Solution for Ghana</strong>';
    echo '<span>Accra · Tema · Kumasi · Cape Coast — professional collection nationwide</span></div>';
    echo '<div class="ghana-brand-stats d-none d-md-flex gap-4">';
    foreach ([['fa-truck', 'GPS Fleet'], ['fa-mobile-screen', 'Mobile Money'], ['fa-recycle', 'Eco Recycling']] as [$ic, $lb]) {
        echo '<span class="ghana-brand-pill"><i class="fa-solid ' . e($ic) . '"></i> ' . e($lb) . '</span>';
    }
    echo '</div></div></div></section>';
}

/** Visual section divider on landing page. */
function uiSectionDivider(string $label = ''): void
{
    echo '<div class="section-divider reveal" aria-hidden="true">';
    if ($label) echo '<span class="section-divider-label">' . e($label) . '</span>';
    echo '</div>';
}

/** Ghana operations gallery mosaic — 6 images. */
function uiGalleryMosaic(array $items): void
{
    echo '<section class="story-mosaic-section landing-section" id="gallery" aria-label="SmartWaste Ghana gallery">';
    echo '<div class="container">';
    echo '<div class="section-header reveal text-center mb-4 mb-lg-5">';
    echo '<span class="section-label">Gallery</span>';
    echo '<h2>Waste management built for Ghana</h2>';
    echo '<p class="text-secondary mb-0 mx-auto" style="max-width:560px">From bin delivery to collection routes — see how SmartWaste serves Ghanaian homes and communities every day</p>';
    echo '</div>';
    echo '<div class="story-mosaic-grid reveal">';
    foreach ($items as $i => [$src, $label]) {
        $wide = $i === 0 || $i === 3 ? ' story-mosaic-item--wide' : '';
        echo '<div class="story-mosaic-item' . $wide . '">';
        echo '<img src="' . e($src) . '" alt="' . e($label) . ' — SmartWaste Ghana" loading="lazy" decoding="async">';
        echo '<span class="story-mosaic-label">' . e($label) . '</span></div>';
    }
    echo '</div></div></section>';
}

/** @deprecated Use uiGalleryMosaic() */
function uiStoryMosaic(array $items): void
{
    uiGalleryMosaic($items);
}

/** Corporate page hero banner. */
function uiCorpHero(string $title, string $subtitle, string $bgImage, ?string $breadcrumb = null): void
{
    echo '<section class="corp-hero reveal">';
    echo '<img src="' . e($bgImage) . '" alt="" class="corp-hero-bg" loading="eager" decoding="async">';
    echo '<div class="corp-hero-overlay"></div>';
    echo '<div class="container corp-hero-content">';
    if ($breadcrumb) {
        echo '<nav aria-label="Breadcrumb" class="corp-breadcrumb"><ol class="breadcrumb mb-3">';
        echo '<li class="breadcrumb-item"><a href="' . baseUrl('home') . '">Home</a></li>';
        echo '<li class="breadcrumb-item active" aria-current="page">' . e($breadcrumb) . '</li>';
        echo '</ol></nav>';
    }
    echo '<h1 class="corp-hero-title">' . e($title) . '</h1>';
    echo '<p class="corp-hero-lead">' . e($subtitle) . '</p>';
    echo '</div></section>';
}

/** Mid-page call-to-action with Ghana background. */
function uiMidPageCta(string $bgImage, string $title, string $desc): void
{
    echo '<section class="landing-section py-0"><div class="container">';
    echo '<div class="mid-page-cta reveal">';
    echo '<img src="' . e($bgImage) . '" alt="" class="mid-page-cta-bg" loading="lazy">';
    echo '<div class="mid-page-cta-overlay"></div>';
    echo '<div class="mid-page-cta-content text-center">';
    echo '<h3 class="fw-bold mb-2">' . e($title) . '</h3>';
    echo '<p class="mb-4 opacity-90">' . e($desc) . '</p>';
    echo '<a href="' . baseUrl('auth/register') . '" class="btn-saas btn-saas-primary btn-saas-lg"><i class="fa-solid fa-user-plus"></i> Join SmartWaste Ghana</a>';
    echo '</div></div></div></section>';
}

function uiGlassCardOpen(string $title, ?string $headerRight = null, ?string $icon = null): void
{
    echo '<div class="glass-card saas-card animate-in mb-4">';
    echo '<div class="saas-card-header">';
    echo '<div class="saas-card-title">' . ($icon ? '<i class="fa-solid ' . e($icon) . ' me-2"></i>' : '') . e($title) . '</div>';
    if ($headerRight) echo '<div class="saas-card-actions">' . $headerRight . '</div>';
    echo '</div><div class="saas-card-body">';
}

function uiGlassCardClose(): void
{
    echo '</div></div>';
}

function uiTableWrapOpen(string $searchPlaceholder = 'Search...', bool $paginated = true, ?string $exportCallback = null): void
{
    echo '<div class="glass-card saas-card animate-in">';
    echo '<div class="saas-table-toolbar">';
    echo '<div class="saas-search"><i class="fa-solid fa-magnifying-glass"></i>';
    echo '<input type="search" class="table-search-input" placeholder="' . e($searchPlaceholder) . '" aria-label="Search table"></div>';
    echo '<div class="table-toolbar-actions">';
    if ($exportCallback) {
        echo '<button type="button" class="btn-saas btn-saas-ghost btn-saas-sm" onclick="' . e($exportCallback) . '"><i class="fa-solid fa-download"></i> Export</button>';
    }
    echo '</div></div>';
    echo '<div class="saas-card-body p-0"><div class="table-responsive saas-table-wrapper">';
    echo '<table class="table saas-table mb-0"' . ($paginated ? ' data-per-page="10"' : '') . '>';
}

function uiTableWrapClose(bool $paginated = true): void
{
    echo '</table></div>';
    if ($paginated) {
        echo '<div class="table-pagination">';
        echo '<span class="pagination-info">Showing results</span>';
        echo '<div class="table-pagination-btns">';
        echo '<button type="button" class="page-btn page-prev" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>';
        echo '<span class="pagination-pages d-flex gap-1"></span>';
        echo '<button type="button" class="page-btn page-next" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>';
        echo '</div></div>';
    }
    echo '</div></div>';
}

function uiQuickActions(array $actions): void
{
    echo '<div class="quick-actions animate-in">';
    foreach ($actions as $a) {
        echo '<a href="' . baseUrl($a['route']) . '" class="quick-action-btn">';
        echo '<i class="fa-solid ' . e($a['icon']) . '"></i> ' . e($a['label']);
        if (!empty($a['badge'])) {
            echo ' <span class="quick-action-badge">' . ((int) $a['badge'] > 9 ? '9+' : (int) $a['badge']) . '</span>';
        }
        echo '</a>';
    }
    echo '</div>';
}

function uiTimelineItem(string $title, string $desc, string $time, string $variant = 'info'): void
{
    echo '<div class="timeline-item">';
    echo '<div class="timeline-dot ' . e($variant) . '"></div>';
    echo '<div class="timeline-content"><strong class="small">' . e($title) . '</strong>';
    echo '<p class="small text-secondary mb-0 mt-1">' . e($desc) . '</p>';
    echo '<div class="timeline-time">' . e($time) . '</div></div></div>';
}

function uiProgressBar(string $label, float $percent, ?string $valueLabel = null): void
{
    echo '<div class="mb-3"><div class="stat-row"><span>' . e($label) . '</span>';
    echo '<span>' . e($valueLabel ?? round($percent) . '%') . '</span></div>';
    echo '<div class="progress-premium"><div class="progress-premium-bar" data-progress="' . min(100, max(0, $percent)) . '" style="width:0"></div></div></div>';
}

/** Circular-style performance metric with animated bar. */
function uiPerformanceMetric(string $label, float $percent, string $variant = 'success'): void
{
    $pct = min(100, max(0, $percent));
    echo '<div class="performance-metric performance-metric-' . e($variant) . ' animate-in">';
    echo '<div class="performance-metric-value">' . e(number_format($pct, 1)) . '<span>%</span></div>';
    echo '<div class="performance-metric-label">' . e($label) . '</div>';
    echo '<div class="progress-premium mt-2"><div class="progress-premium-bar" data-progress="' . $pct . '" style="width:0"></div></div>';
    echo '</div>';
}

function uiCalendarWidget(): void
{
    echo '<div class="calendar-widget" data-calendar>';
    echo '<div class="calendar-header"><strong class="calendar-title"></strong></div>';
    echo '<div class="calendar-grid">';
    foreach (['Su','Mo','Tu','We','Th','Fr','Sa'] as $d) {
        echo '<div class="calendar-day-label">' . $d . '</div>';
    }
    echo '</div></div>';
}

function uiStatusMini(string $label, string|int $value, ?string $variant = null): void
{
    echo '<div class="status-mini-card"><div class="value">' . e((string)$value) . '</div>';
    echo '<div class="label">' . e($label) . '</div></div>';
}

function notificationRoute(string $role): string
{
    return match ($role) {
        'resident' => 'resident/notifications',
        'collector' => 'collector/dashboard',
        default => 'admin/dashboard',
    };
}

function uiSortableTh(string $label): string
{
    return '<th class="sortable">' . e($label) . ' <i class="fa-solid fa-sort sort-icon"></i></th>';
}

/**
 * Render a CSS wheelie bin preview (colour via --bin-color).
 */
function uiMiniBin(?string $size, ?string $colorName, string $classes = '', ?string $id = null): void
{
    if (!$size) {
        return;
    }
    $hex = binColorHex($colorName);
    $sizeClass = 'size-' . substr($size, 0, 1);
    $idAttr = $id ? ' id="' . e($id) . '"' : '';
    $label = ucfirst($colorName ?? 'green') . ' ' . ucfirst($size) . ' bin';
    echo '<div class="mini-bin ' . e($sizeClass) . ' ' . e($classes) . '" style="--bin-color:' . e($hex) . '"'
        . $idAttr . ' role="img" aria-label="' . e($label) . '"></div>';
}

function uiPageLoader(): void
{
    echo '<div class="page-loader" id="pageLoader"><div class="loader-brand">';
    echo '<div class="loader-icon"><i class="fa-solid fa-recycle"></i></div>';
    echo '<div class="loader-bar"><span></span></div></div></div>';
}
