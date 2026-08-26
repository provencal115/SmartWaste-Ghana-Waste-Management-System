<?php uiPageHeader('Cash Payment Activity', 'Monitor cash collections and verification status'); ?>

<form method="get" action="<?= baseUrl('admin/cash-payments') ?>" class="glass-card saas-card p-3 mb-4 saas-form">
    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                <?php foreach (['pending' => 'Pending', 'approved' => 'Verified', 'rejected' => 'Rejected', 'review' => 'Under Review'] as $k => $l): ?>
                <option value="<?= $k ?>" <?= ($filters['status'] ?? '') === $k ? 'selected' : '' ?>><?= e($l) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">From</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small">To</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($filters['date_to'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn-saas btn-saas-primary btn-saas-sm w-100"><i class="fa-solid fa-filter me-1"></i>Filter</button>
        </div>
    </div>
</form>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Total Cash Payments', (int)($stats['total'] ?? 0), 'fa-money-bill', 'primary', 'All records', 0);
    uiKpi('Pending Verification', (int)($stats['pending'] ?? 0), 'fa-clock', 'warning', 'Needs review', 1);
    uiKpi('Verified Cash', (int)($stats['approved'] ?? 0), 'fa-circle-check', 'success', 'Approved', 2);
    uiKpi('Cash Revenue', formatCurrency($stats['revenue'] ?? 0), 'fa-sack-dollar', 'purple', 'Verified total', 3);
    ?>
</div>

<?php uiGlassCardOpen('Cash Transactions', null, 'fa-list'); ?>
<?php if (empty($payments)): uiEmptyState('fa-receipt', 'No cash payments', 'No records match your filters.', null, 'wallet'); ?>
<?php else: ?>
<div class="table-responsive saas-table-wrapper">
<table class="saas-table">
    <thead>
        <tr>
            <th>Reference</th>
            <th>Customer</th>
            <th>Collector</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
            <th>Invoice</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
            <td class="font-monospace small"><?= e($p['receipt_number']) ?></td>
            <td><?= e($p['first_name'] . ' ' . $p['last_name']) ?></td>
            <td><?= e(trim(($p['collector_first'] ?? '') . ' ' . ($p['collector_last'] ?? '')) ?: '—') ?></td>
            <td><?= formatCurrency($p['amount_received'] ?? $p['amount']) ?></td>
            <td><?= paymentStatusBadge($p) ?></td>
            <td class="small"><?= formatDateTime($p['created_at']) ?></td>
            <td><a href="<?= baseUrl('api/invoice') ?>&ref=<?= urlencode($p['invoice_number'] ?? $p['receipt_number']) ?>" target="_blank" class="btn-saas btn-saas-ghost btn-saas-sm"><i class="fa-solid fa-print"></i></a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
<?php uiGlassCardClose(); ?>
