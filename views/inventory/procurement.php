<?php
$prefillSize = $_GET['size'] ?? '';
$prefillQty = (int)($_GET['qty'] ?? 0);
$sizeOptions = ['small' => '120L', 'medium' => '240L', 'large' => '360L'];
?>
<link href="<?= asset('css/inventory-forecast.css') ?>" rel="stylesheet">

<?php uiPageHeader(
    'Procurement Requests',
    'Create and track bin procurement based on forecast recommendations',
    '<a href="' . baseUrl('inventory/dashboard') . '" class="btn-saas btn-saas-ghost btn-saas-sm"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>'
); ?>

<?php if (empty($procurementReady)): ?>
<div class="alert alert-warning animate-in mb-4">
    <i class="fa-solid fa-triangle-exclamation me-2"></i>
    Procurement storage is not set up yet. Run <code>php scripts/run_inventory_forecast_migration.php</code> or open the inventory dashboard once to auto-create the table.
</div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Pending', $stats['pending'] ?? 0, 'fa-clock', 'warning', null, 0);
    uiKpi('Approved', $stats['approved'] ?? 0, 'fa-check', 'info', null, 1);
    uiKpi('Ordered', $stats['ordered'] ?? 0, 'fa-truck', 'primary', null, 2);
    uiKpi('Received', $stats['received'] ?? 0, 'fa-box-open', 'success', null, 3);
    ?>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="glass-card saas-card animate-in">
            <div class="saas-card-header">
                <div class="saas-card-title"><i class="fa-solid fa-cart-plus me-2"></i>New Procurement Request</div>
            </div>
            <div class="saas-card-body">
                <form method="post" action="<?= baseUrl('inventory/procurement') ?>" data-validate>
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label">Bin Size</label>
                        <select name="bin_size" class="form-select" required id="procureSize">
                            <?php foreach ($sizeOptions as $val => $label): ?>
                            <option value="<?= e($val) ?>"<?= $prefillSize === $val ? ' selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" class="form-control" min="1" max="9999" required
                               value="<?= $prefillQty > 0 ? $prefillQty : '' ?>" placeholder="e.g. 30">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <input type="text" name="reason" class="form-control" maxlength="255"
                               placeholder="Low stock / forecast replenishment">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Optional supplier or delivery notes"></textarea>
                    </div>

                    <?php foreach ($forecasts as $f): ?>
                    <div class="inv-procure-rec mb-3 forecast-hint" data-size="<?= e($f['size']) ?>"<?= $prefillSize && $prefillSize !== $f['size'] ? ' style="display:none"' : '' ?>>
                        <strong><?= e($f['label']) ?> forecast:</strong>
                        Stock <?= (int)$f['current_stock'] ?> · Usage <?= !empty($f['no_history']) ? 'N/A' : e(number_format((float)$f['avg_monthly_usage'], 1)) . '/mo' ?>
                        <?php if ($f['recommended_reorder'] !== null): ?>
                        · Recommend <strong><?= (int)$f['recommended_reorder'] ?></strong>
                        <?php endif; ?>
                        <?php if (!empty($f['no_history'])): ?>
                        <div class="inv-limited-note mt-1 mb-0"><i class="fa-solid fa-circle-info"></i> No sufficient inventory history</div>
                        <?php elseif (!empty($f['limited_data'])): ?>
                        <div class="inv-limited-note mt-1 mb-0"><i class="fa-solid fa-circle-info"></i> Limited historical data</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                    <button type="submit" class="btn-saas btn-saas-primary w-100"<?= empty($procurementReady) ? ' disabled' : '' ?>>
                        <i class="fa-solid fa-paper-plane me-1"></i> Submit Request
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="glass-card saas-card animate-in">
            <div class="saas-card-header">
                <div class="saas-card-title"><i class="fa-solid fa-list me-2"></i>Request History</div>
            </div>
            <div class="saas-card-body p-0">
                <?php if (!$requests): ?>
                    <?php uiEmptyState('fa-cart-shopping', 'No procurement requests', 'Create a request when stock falls below forecast thresholds.'); ?>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover saas-table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Size</th>
                                <th>Qty</th>
                                <th>Recommended</th>
                                <th>Status</th>
                                <th>Requested By</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $r):
                                $statusClass = match ($r['status']) {
                                    'pending' => 'warning',
                                    'approved' => 'info',
                                    'ordered' => 'primary',
                                    'received' => 'success',
                                    'cancelled' => 'secondary',
                                    default => 'secondary',
                                };
                            ?>
                            <tr>
                                <td>#<?= (int)$r['id'] ?></td>
                                <td><?= e(binCapacity($r['bin_size'])) ?>L</td>
                                <td><strong><?= (int)$r['quantity'] ?></strong></td>
                                <td><?= (int)($r['recommended_quantity'] ?? 0) ?></td>
                                <td><span class="status-pill status-<?= e($statusClass) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                                <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
                                <td><?= e(formatDateTime($r['created_at'])) ?></td>
                                <td>
                                    <?php if ($r['status'] === 'pending'): ?>
                                    <form method="post" action="<?= baseUrl('inventory/procurement/status') ?>" class="d-inline">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="btn-saas btn-saas-ghost btn-saas-sm" title="Approve"><i class="fa-solid fa-check"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if (in_array($r['status'], ['pending', 'approved'], true)): ?>
                                    <form method="post" action="<?= baseUrl('inventory/procurement/status') ?>" class="d-inline">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="status" value="ordered">
                                        <button type="submit" class="btn-saas btn-saas-ghost btn-saas-sm" title="Mark ordered"><i class="fa-solid fa-truck"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($r['status'] === 'ordered'): ?>
                                    <form method="post" action="<?= baseUrl('inventory/procurement/status') ?>" class="d-inline">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="status" value="received">
                                        <button type="submit" class="btn-saas btn-saas-ghost btn-saas-sm" title="Mark received"><i class="fa-solid fa-box-open"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('procureSize')?.addEventListener('change', function () {
    document.querySelectorAll('.forecast-hint').forEach(function (el) {
        el.style.display = el.dataset.size === this.value ? '' : 'none';
    }.bind(this));
});
</script>
