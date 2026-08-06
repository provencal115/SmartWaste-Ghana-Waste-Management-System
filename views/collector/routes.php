<?php uiPageHeader('Collection Route', 'Optimized route for today'); ?>
<div class="map-placeholder glass-card mb-4 animate-in"><i class="fa-solid fa-map-location-dot d-block mb-2"></i><strong>GPS Route Map</strong><p class="text-secondary small mb-0 mt-1">Interactive map with optimized collection path</p></div>
<?php uiGlassCardOpen('Route Stops', count($schedule) . ' stops', 'fa-location-dot'); ?>
<?php if (empty($schedule)): uiEmptyState('fa-route', 'No route assigned', 'Check back later for your route.', null, 'route'); ?>
<?php else: foreach ($schedule as $i => $s): ?>
<div class="list-item"><div class="d-flex align-items-center gap-3">
    <span class="badge rounded-circle bg-success" style="width:32px;height:32px;line-height:22px"><?= $i + 1 ?></span>
    <div><strong><?= e($s['first_name'] . ' ' . $s['last_name']) ?></strong><br><small class="text-secondary"><?= e($s['address']) ?></small></div>
</div><?= statusBadge($s['pickup_status']) ?></div>
<?php endforeach; endif; ?>
<?php uiGlassCardClose(); ?>
