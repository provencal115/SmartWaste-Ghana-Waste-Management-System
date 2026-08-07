<?php
$routeData = $optimizedRoute ? OptimizedRouteModel::decodeRouteData($optimizedRoute['route_data'] ?? null) : null;
$progress = $optimizedRoute ? OptimizedRouteModel::progressPercent($optimizedRoute) : 0;
?>
<?php uiPageHeader('Collection Route', $optimizedRoute ? e($optimizedRoute['route_name']) : 'Today\'s collection route'); ?>

<?php if ($optimizedRoute): ?>
<div class="glass-card saas-card animate-in mb-4">
    <div class="saas-card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h6 class="fw-bold mb-1"><i class="fa-solid fa-route me-2 text-success"></i>Route Progress</h6>
                <span class="text-secondary small"><?= (int)$optimizedRoute['completed_stops'] ?> / <?= (int)$optimizedRoute['total_stops'] ?> collections completed</span>
            </div>
            <div class="text-end">
                <?= statusBadge($optimizedRoute['status']) ?>
                <div class="small text-secondary mt-1"><?= number_format((float)$optimizedRoute['estimated_distance_km'], 1) ?> km · <?= RouteOptimizer::formatDuration((int)$optimizedRoute['estimated_duration_min']) ?></div>
            </div>
        </div>
        <div class="route-progress-bar mb-1">
            <div class="route-progress-fill" style="width:<?= $progress ?>%"></div>
        </div>
        <div class="text-end small fw-semibold text-success"><?= $progress ?>%</div>
    </div>
</div>
<?php endif; ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<link href="<?= asset('css/route-optimization.css') ?>" rel="stylesheet">

<?php if ($routeData): ?>
<div id="routeMap" class="route-map-container glass-card mb-4 animate-in"
     data-route='<?= e(json_encode($routeData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'></div>
<?php else: ?>
<div class="map-placeholder glass-card mb-4 animate-in">
    <i class="fa-solid fa-map-location-dot d-block mb-2"></i>
    <strong>No optimised route yet</strong>
    <p class="text-secondary small mb-0 mt-1">Your administrator will assign an optimised route for today.</p>
</div>
<?php endif; ?>

<?php uiGlassCardOpen('Today\'s Collections', count($schedule) . ' stops', 'fa-location-dot'); ?>
<?php if (empty($schedule)): ?>
<?php uiEmptyState('fa-route', 'No collections today', 'Check back later for your route.', null, 'route'); ?>
<?php else: ?>
<div class="route-timeline px-2 pb-2">
<?php foreach ($schedule as $i => $s):
    $binSize = $s['assigned_bin_size'] ?? $s['selected_bin_size'] ?? 'medium';
    $order = $s['stop_order'] ?? ($i + 1);
    $hasGps = !empty($s['gps_lat']) && !empty($s['gps_lng']);
    $navUrl = $hasGps
        ? 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($s['gps_lat'] . ',' . $s['gps_lng'])
        : 'https://www.google.com/maps/search/?api=1&query=' . urlencode(trim($s['address'] . ', ' . ($s['city'] ?? 'Ghana')));
?>
<div class="route-stop-card mb-2 animate-in">
    <div class="route-stop-number"><?= (int)$order ?></div>
    <div class="flex-grow-1 min-w-0">
        <strong><?= e($s['first_name'] . ' ' . $s['last_name']) ?></strong>
        <div class="text-secondary small"><?= e(binCapacity($binSize)) ?>L · <?= e($s['zone_name'] ?? '') ?></div>
        <div class="text-secondary small text-truncate"><?= e($s['address']) ?></div>
        <div class="small mt-1"><?= !empty($s['preferred_time']) ? date('g:i A', strtotime($s['preferred_time'])) : 'Any time' ?></div>
    </div>
    <div class="d-flex flex-column align-items-end gap-2">
        <?= statusBadge($s['pickup_status'] ?? $s['status']) ?>
        <a href="<?= e($navUrl) ?>" target="_blank" rel="noopener" class="btn-saas btn-saas-ghost btn-saas-sm" title="Navigate">
            <i class="fa-solid fa-diamond-turn-right"></i>
        </a>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php uiGlassCardClose(); ?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="<?= asset('js/route-map.js') ?>"></script>
