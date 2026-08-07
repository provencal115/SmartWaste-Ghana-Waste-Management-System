<?php
uiPageHeader(
    'Route Optimisation',
    'Generate efficient collection sequences using GPS coordinates and nearest-neighbour routing',
    '<a href="' . baseUrl('admin/routes') . '" class="btn-saas btn-saas-ghost btn-saas-sm"><i class="fa-solid fa-map"></i> Zones & Routes</a>'
);
?>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Total Routes', $routeStats['total_routes'], 'fa-route', 'primary', 'All optimisation runs', 0);
    uiKpi('Active Today', $routeStats['today_routes'], 'fa-calendar-day', 'success', date('l, j M Y'), 1);
    uiKpi('Avg Distance', ($routeStats['avg_distance_km'] ?? 0) . ' km', 'fa-road', 'info', 'Straight-line + road factor', 2);
    uiKpi('Avg Duration', RouteOptimizer::formatDuration((int)($routeStats['avg_duration_min'] ?? 0)), 'fa-clock', 'warning', 'Estimated completion', 3);
    ?>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="glass-card saas-card animate-in mb-4">
            <div class="saas-card-header">
                <div class="saas-card-title"><i class="fa-solid fa-sliders me-2 text-success"></i>Optimise Route</div>
            </div>
            <div class="saas-card-body">
                <form method="post" action="<?= baseUrl('admin/route-optimisation') ?>" id="optimiseForm">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="optimise" id="optimiseAction">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Collection Date</label>
                        <input type="date" name="collection_date" class="form-control" required
                               value="<?= e($selectedDate) ?>" id="optDate">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Collection Zone</label>
                        <select name="zone_id" class="form-select" required id="optZone">
                            <option value="">Select zone…</option>
                            <?php foreach ($zones as $z): ?>
                            <option value="<?= (int)$z['id'] ?>"<?= $selectedZone === (int)$z['id'] ? ' selected' : '' ?>>
                                <?= e($z['name']) ?> (<?= (int)($z['resident_count'] ?? 0) ?> residents)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Garbage Collector</label>
                        <select name="collector_id" class="form-select" required id="optCollector">
                            <option value="">Select collector…</option>
                            <?php foreach ($collectors as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" data-zone="<?= (int)($c['zone_id'] ?? 0) ?>"
                                <?= $selectedCollector === (int)$c['id'] ? ' selected' : '' ?>>
                                <?= e($c['first_name'] . ' ' . $c['last_name']) ?> (<?= e($c['employee_id']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Collection Vehicle</label>
                        <select name="truck_id" class="form-select" id="optTruck">
                            <option value="">Select vehicle…</option>
                            <?php foreach ($trucks as $t): ?>
                            <option value="<?= (int)$t['id'] ?>" data-zone="<?= (int)($t['zone_id'] ?? 0) ?>"
                                <?= $selectedTruck === (int)$t['id'] ? ' selected' : '' ?>>
                                <?= e($t['plate_number']) ?> — <?= e(ucfirst(str_replace('_', ' ', $t['status'] ?? 'active'))) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes <span class="text-secondary fw-normal">(optional)</span></label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Truck breakdown — re-routing remaining stops"></textarea>
                    </div>
                    <?php if ($previewCount > 0): ?>
                    <div class="alert alert-light border small mb-3">
                        <i class="fa-solid fa-circle-info text-success me-1"></i>
                        <strong><?= $previewCount ?></strong> scheduled collection<?= $previewCount === 1 ? '' : 's' ?> found for this selection.
                    </div>
                    <?php endif; ?>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn-saas btn-saas-primary">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Optimise Route
                        </button>
                        <?php if ($optimizedRoute): ?>
                        <button type="button" class="btn-saas btn-saas-outline" id="reoptimiseBtn">
                            <i class="fa-solid fa-rotate"></i> Re-Optimise Route
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
                <p class="text-secondary small mt-3 mb-0">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    Uses straight-line Haversine distance with nearest-neighbour ordering — not live road-network routing.
                </p>
            </div>
        </div>

        <div class="glass-card saas-card animate-in">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-chart-column me-2"></i>Route Analytics</div></div>
            <div class="saas-card-body">
                <ul class="list-group list-group-flush route-analytics-list">
                    <li class="list-group-item d-flex justify-content-between"><span>Optimised</span><strong><?= (int)$routeStats['optimised_routes'] ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Active / In Progress</span><strong><?= (int)$routeStats['active_routes'] ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>Completed</span><strong><?= (int)$routeStats['completed_routes'] ?></strong></li>
                </ul>
                <?php if (!empty($routeStats['collections_per_vehicle'])): ?>
                <h6 class="fw-semibold mt-3 mb-2 small text-secondary">Collections per Vehicle</h6>
                <ul class="list-group list-group-flush">
                    <?php foreach ($routeStats['collections_per_vehicle'] as $v): ?>
                    <li class="list-group-item d-flex justify-content-between small">
                        <span><?= e($v['plate_number']) ?></span>
                        <span><?= (int)$v['total_collections'] ?> stops · <?= (int)$v['route_count'] ?> routes</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <?php if ($optimizedRoute):
            $data = $optimizedRoute['route_data_decoded'] ?? OptimizedRouteModel::decodeRouteData($optimizedRoute['route_data'] ?? null);
            $stops = $data['stops'] ?? [];
            $statusLabel = ucfirst(str_replace('_', ' ', $optimizedRoute['status']));
        ?>
        <div class="glass-card saas-card animate-in mb-4 route-summary-card">
            <div class="saas-card-header flex-wrap gap-2">
                <div class="saas-card-title"><i class="fa-solid fa-map-location-dot me-2 text-success"></i><?= e($optimizedRoute['route_name']) ?></div>
                <?= statusBadge($optimizedRoute['status']) ?>
            </div>
            <div class="saas-card-body">
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-lg-4">
                        <div class="route-stat-box"><span class="route-stat-label">Date</span><strong><?= formatDate($optimizedRoute['collection_date']) ?></strong></div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="route-stat-box"><span class="route-stat-label">Collector</span><strong><?= e($optimizedRoute['collector_name']) ?></strong></div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="route-stat-box"><span class="route-stat-label">Vehicle</span><strong><?= e($optimizedRoute['plate_number'] ?? '—') ?></strong></div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="route-stat-box"><span class="route-stat-label">Collections</span><strong><?= (int)$optimizedRoute['total_stops'] ?></strong></div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="route-stat-box"><span class="route-stat-label">Est. Distance</span><strong><?= number_format((float)$optimizedRoute['estimated_distance_km'], 1) ?> km</strong></div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="route-stat-box"><span class="route-stat-label">Est. Duration</span><strong><?= RouteOptimizer::formatDuration((int)$optimizedRoute['estimated_duration_min']) ?></strong></div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="route-stat-box"><span class="route-stat-label">Starting</span><strong class="small"><?= e($data['depot']['label'] ?? 'Zone depot') ?></strong></div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="route-stat-box"><span class="route-stat-label">Algorithm</span><strong><?= e(str_replace('_', ' ', $optimizedRoute['algorithm'])) ?></strong></div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="route-stat-box"><span class="route-stat-label">Version</span><strong>v<?= (int)$optimizedRoute['version'] ?></strong></div>
                    </div>
                </div>

                <div id="routeMap" class="route-map-container mb-4" data-route='<?= e(json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'></div>

                <h6 class="fw-bold mb-3"><i class="fa-solid fa-list-ol me-2 text-success"></i>Collection Sequence — <?= e(strtoupper($optimizedRoute['zone_name'])) ?></h6>
                <div class="route-timeline">
                    <?php foreach ($stops as $stop): ?>
                    <div class="route-timeline-item animate-in">
                        <div class="route-stop-number"><?= (int)$stop['order'] ?></div>
                        <div class="route-stop-body">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <strong><?= e($stop['customer_name']) ?></strong>
                                    <div class="text-secondary small"><?= e($stop['address']) ?></div>
                                    <?php if (($stop['location_source'] ?? '') !== 'gps'): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis mt-1"><i class="fa-solid fa-location-crosshairs"></i> GPS unavailable — <?= e($stop['location_label'] ?? 'address estimate') ?></span>
                                    <?php else: ?>
                                    <span class="badge bg-success-subtle text-success mt-1"><i class="fa-solid fa-satellite"></i> GPS</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-light text-dark border"><?= e(binCapacity($stop['bin_size'] ?? 'medium')) ?>L</span>
                                    <?php if (!empty($stop['preferred_time'])): ?>
                                    <div class="small text-secondary mt-1"><?= date('g:i A', strtotime($stop['preferred_time'])) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="glass-card saas-card animate-in mb-4">
            <div class="saas-card-body text-center py-5">
                <i class="fa-solid fa-route fa-3x text-success mb-3 opacity-50"></i>
                <h5 class="fw-bold">No Optimised Route Yet</h5>
                <p class="text-secondary mb-0 mx-auto" style="max-width:420px">
                    Select a collection date, zone, collector, and vehicle — then click <strong>Optimise Route</strong> to generate an efficient collection sequence.
                </p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($routeHistory)): ?>
        <div class="glass-card saas-card animate-in">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-clock-rotate-left me-2"></i>Recent Optimisations</div></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead><tr><th>Route</th><th>Date</th><th>Collector</th><th>Stops</th><th>Distance</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($routeHistory as $rh): ?>
                    <tr>
                        <td><strong><?= e($rh['route_name']) ?></strong><small class="text-secondary d-block">v<?= (int)$rh['version'] ?></small></td>
                        <td><?= formatDate($rh['collection_date']) ?></td>
                        <td><?= e($rh['collector_name']) ?></td>
                        <td><?= (int)$rh['total_stops'] ?></td>
                        <td><?= number_format((float)$rh['estimated_distance_km'], 1) ?> km</td>
                        <td><?= statusBadge($rh['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<link href="<?= asset('css/route-optimization.css') ?>" rel="stylesheet">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="<?= asset('js/route-map.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const reBtn = document.getElementById('reoptimiseBtn');
    const form = document.getElementById('optimiseForm');
    const action = document.getElementById('optimiseAction');
    if (reBtn && form && action) {
        reBtn.addEventListener('click', function () {
            if (confirm('Re-optimise this route? Previous version will be archived.')) {
                action.value = 'reoptimise';
                form.submit();
            }
        });
    }
    const zone = document.getElementById('optZone');
    const collector = document.getElementById('optCollector');
    const truck = document.getElementById('optTruck');
    function filterByZone(select) {
        if (!zone || !select) return;
        const zid = zone.value;
        select.querySelectorAll('option[data-zone]').forEach(function (opt) {
            opt.hidden = zid && opt.dataset.zone !== zid;
        });
    }
    if (zone) {
        zone.addEventListener('change', function () {
            filterByZone(collector);
            filterByZone(truck);
        });
        filterByZone(collector);
        filterByZone(truck);
    }
});
</script>
