<?php
$completionRate = ($stats['today_total'] ?? 0) > 0
    ? round(($stats['today_completed'] / max(1, $stats['today_total'])) * 100)
    : 0;
?>
<?php uiPageHeader('Collector Dashboard', 'Welcome, ' . e($user['first_name']) . ' · ' . e($collector['employee_id'] ?? 'Collector')); ?>

<?php uiQuickActions([
    ['icon' => 'fa-qrcode', 'label' => 'Scan Bin', 'route' => 'collector/scan'],
    ['icon' => 'fa-triangle-exclamation', 'label' => 'Report Issue', 'route' => 'collector/reports'],
    ['icon' => 'fa-route', 'label' => 'View Route', 'route' => 'collector/routes'],
]); ?>

<div class="row g-4 mb-4">
    <?php
    uiKpi("Today's Pickups", (int)$stats['today_total'], 'fa-map-pin', 'primary', 'Scheduled for today', 0);
    uiKpi('Completed', (int)$stats['today_completed'], 'fa-circle-check', 'success', $completionRate . '% done', 1);
    uiKpi('Pending', (int)$stats['today_pending'], 'fa-clock', 'warning', 'Awaiting collection', 2);
    uiKpi('In Progress', (int)$stats['today_in_progress'], 'fa-truck-fast', 'info', 'Active now', 3);
    ?>
</div>

<?php if (!empty($todayRoute)): ?>
<?php $routeProgress = OptimizedRouteModel::progressPercent($todayRoute); ?>
<div class="glass-card saas-card animate-in mb-4">
    <div class="saas-card-header flex-wrap gap-2">
        <div class="saas-card-title"><i class="fa-solid fa-route me-2 text-success"></i>Today's Route</div>
        <a href="<?= baseUrl('collector/routes') ?>" class="btn-saas btn-saas-sm btn-saas-outline">View Map</a>
    </div>
    <div class="saas-card-body">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
            <div>
                <strong><?= e($todayRoute['route_name']) ?></strong>
                <div class="text-secondary small"><?= (int)$todayRoute['completed_stops'] ?> / <?= (int)$todayRoute['total_stops'] ?> completed · <?= number_format((float)$todayRoute['estimated_distance_km'], 1) ?> km</div>
            </div>
            <?= statusBadge($todayRoute['status']) ?>
        </div>
        <div class="route-progress-bar mb-3">
            <div class="route-progress-fill" style="width:<?= $routeProgress ?>%"></div>
        </div>
        <?php if (!empty($todayStops)): ?>
        <div class="row g-2">
            <?php foreach (array_slice($todayStops, 0, 5) as $i => $stop):
                $binSize = $stop['assigned_bin_size'] ?? $stop['selected_bin_size'] ?? 'medium';
                $order = $stop['stop_order'] ?? ($i + 1);
            ?>
            <div class="col-md-6">
                <div class="route-stop-card">
                    <div class="route-stop-number" style="width:30px;height:30px;font-size:0.85rem"><?= (int)$order ?></div>
                    <div class="flex-grow-1 min-w-0">
                        <strong class="small"><?= e($stop['first_name'] . ' ' . $stop['last_name']) ?></strong>
                        <div class="text-secondary" style="font-size:0.75rem"><?= e(binCapacity($binSize)) ?>L · <?= !empty($stop['preferred_time']) ? date('g:i A', strtotime($stop['preferred_time'])) : 'Any time' ?></div>
                    </div>
                    <?= statusBadge($stop['pickup_status'] ?? 'pending') ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($todayStops) > 5): ?>
        <p class="text-secondary small mb-0 mt-2">+ <?= count($todayStops) - 5 ?> more stops on the full route map.</p>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<link href="<?= asset('css/route-optimization.css') ?>" rel="stylesheet">
<?php endif; ?>

<div class="glass-card saas-card animate-in mb-4">
    <div class="saas-card-header flex-wrap gap-2">
        <div class="saas-card-title"><i class="fa-solid fa-filter me-2"></i>Pickup Requests</div>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ([
                'today' => 'Today', 'upcoming' => 'Upcoming', 'completed' => 'Completed',
                'missed' => 'Missed', 'cancelled' => 'Cancelled', 'all' => 'All',
            ] as $key => $label): ?>
            <a href="<?= baseUrl('collector/dashboard', ['filter' => $key]) ?>"
               class="btn-saas btn-saas-sm <?= $filter === $key ? 'btn-saas-primary' : 'btn-saas-ghost' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="saas-card-body p-0">
        <?php if (empty($pickups)): ?>
        <div class="p-4"><?php uiEmptyState('fa-calendar-day', 'No pickups found', 'No pickup requests match the selected filter.', null, 'calendar'); ?></div>
        <?php else: ?>
        <?php uiTableWrapOpen('Search pickups by resident, address, zone, or ID...'); ?>
        <thead>
            <tr>
                <?= uiSortableTh('Pickup ID') ?>
                <?= uiSortableTh('Resident') ?>
                <?= uiSortableTh('Contact') ?>
                <?= uiSortableTh('Address') ?>
                <?= uiSortableTh('Zone') ?>
                <?= uiSortableTh('Bin') ?>
                <?= uiSortableTh('Type') ?>
                <?= uiSortableTh('Date') ?>
                <?= uiSortableTh('Time') ?>
                <?= uiSortableTh('Pickup Status') ?>
                <?= uiSortableTh('Payment') ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pickups as $p):
                $binSize = $p['assigned_bin_size'] ?? $p['selected_bin_size'] ?? '—';
                $payStatus = $p['last_payment_status'] ?? ($p['outstanding_balance'] > 0 ? 'pending' : 'completed');
            ?>
            <tr>
                <td><code class="small">#<?= str_pad((string)$p['id'], 5, '0', STR_PAD_LEFT) ?></code></td>
                <td><strong><?= e($p['first_name'] . ' ' . $p['last_name']) ?></strong></td>
                <td class="small"><a href="tel:<?= e($p['phone']) ?>"><?= e($p['phone'] ?? '—') ?></a></td>
                <td class="small text-secondary"><?= e($p['address'] . ($p['city'] ? ', ' . $p['city'] : '')) ?></td>
                <td><span class="status-pill status-info"><span class="status-dot"></span><?= e($p['zone_name'] ?? '—') ?></span></td>
                <td><span class="badge bg-light text-dark border"><?= e(ucfirst($binSize)) ?></span></td>
                <td><?= $p['schedule_type'] === 'recurring' ? 'Recurring' : 'One-Time' ?></td>
                <td><?= formatDate($p['preferred_date']) ?></td>
                <td><?= e($p['preferred_time'] ?? 'Any time') ?></td>
                <td><?= statusBadge($p['pickup_status'] ?? $p['status']) ?></td>
                <td><?= statusBadge($payStatus) ?></td>
                <td>
                    <button type="button" class="btn-saas btn-saas-ghost btn-saas-sm pickup-detail-btn me-1"
                            data-bs-toggle="modal" data-bs-target="#pickupDetailModal"
                            data-id="<?= (int)$p['id'] ?>"
                            data-resident="<?= e($p['first_name'] . ' ' . $p['last_name']) ?>"
                            data-phone="<?= e($p['phone'] ?? '') ?>"
                            data-address="<?= e($p['address'] . ', ' . ($p['city'] ?? '')) ?>"
                            data-zone="<?= e($p['zone_name'] ?? '') ?>"
                            data-bin="<?= e(ucfirst($binSize)) ?>"
                            data-type="<?= $p['schedule_type'] === 'recurring' ? 'Recurring' : 'One-Time' ?>"
                            data-date="<?= e($p['preferred_date']) ?>"
                            data-time="<?= e($p['preferred_time'] ?? 'Any time') ?>"
                            data-status="<?= e($p['pickup_status'] ?? $p['status']) ?>"
                            data-notes="<?= e($p['collection_notes'] ?? '') ?>"
                            data-collector-notes="<?= e($p['collector_notes'] ?? '') ?>">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                    <a href="<?= baseUrl('collector/cash-payment') ?>&schedule_id=<?= (int)$p['id'] ?>" class="btn-saas btn-saas-primary btn-saas-sm" title="Collect Cash"><i class="fa-solid fa-money-bill-wave"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <?php uiTableWrapClose(); ?>
        <?php endif; ?>
    </div>
</div>

<!-- Pickup detail & update modal -->
<div class="modal fade" id="pickupDetailModal" tabindex="-1" aria-labelledby="pickupDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="pickupDetailLabel"><i class="fa-solid fa-truck me-2 text-success"></i>Pickup Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4" id="pickupDetailGrid"></div>
                <form method="POST" action="<?= baseUrl('collector/pickup') ?>" enctype="multipart/form-data" class="saas-form" id="pickupUpdateForm">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="schedule_id" id="modalScheduleId">
                    <input type="hidden" name="redirect_filter" value="<?= e($filter) ?>">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-pen-to-square me-2"></i>Update Pickup</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Pickup Status</label>
                            <select name="pickup_status" class="form-select" required>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="delayed">Delayed</option>
                                <option value="missed">Missed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Proof of Collection (photo)</label>
                            <input type="file" name="proof_photo" class="form-control" accept="image/*">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Collector Notes</label>
                            <textarea name="collector_notes" class="form-control" rows="3" placeholder="Notes after completing this pickup..."></textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn-saas btn-saas-primary"><i class="fa-solid fa-check"></i> Save Update</button>
                        <button type="button" class="btn-saas btn-saas-ghost" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('pickupDetailModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', (e) => {
        const btn = e.relatedTarget;
        if (!btn) return;
        document.getElementById('modalScheduleId').value = btn.dataset.id;
        document.getElementById('pickupDetailLabel').innerHTML =
            '<i class="fa-solid fa-truck me-2 text-success"></i>Pickup #' + String(btn.dataset.id).padStart(5, '0');
        const fields = [
            ['Resident', btn.dataset.resident], ['Phone', btn.dataset.phone], ['Address', btn.dataset.address],
            ['Zone', btn.dataset.zone], ['Bin Size', btn.dataset.bin], ['Collection Type', btn.dataset.type],
            ['Scheduled Date', btn.dataset.date], ['Scheduled Time', btn.dataset.time], ['Current Status', btn.dataset.status],
        ];
        document.getElementById('pickupDetailGrid').innerHTML = fields.map(([l, v]) =>
            `<div class="col-md-6"><div class="list-item border rounded-3 px-3 py-2"><span class="text-secondary small d-block">${l}</span><strong>${v || '—'}</strong></div></div>`
        ).join('') + (btn.dataset.notes ? `<div class="col-12"><div class="alert alert-light small mb-0"><strong>Resident notes:</strong> ${btn.dataset.notes}</div></div>` : '');
        const form = document.getElementById('pickupUpdateForm');
        form.querySelector('[name=pickup_status]').value = btn.dataset.status === 'scheduled' ? 'pending' : (btn.dataset.status || 'pending');
        form.querySelector('[name=collector_notes]').value = btn.dataset.collectorNotes || '';
    });
});
</script>
