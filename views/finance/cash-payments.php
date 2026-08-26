<?php uiPageHeader('Cash Payment Verification', 'Review and approve collector cash payments'); ?>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Pending Verification', (int)($stats['pending'] ?? 0), 'fa-clock', 'warning', 'Awaiting review', 0);
    uiKpi('Verified Cash', (int)($stats['approved'] ?? 0), 'fa-circle-check', 'success', 'Approved payments', 1);
    uiKpi('Rejected', (int)($stats['rejected'] ?? 0), 'fa-ban', 'danger', 'Declined payments', 2);
    uiKpi('Cash Revenue', formatCurrency($stats['revenue'] ?? 0), 'fa-sack-dollar', 'purple', 'Verified total', 3);
    ?>
</div>

<?php uiGlassCardOpen('Pending Cash Payments', null, 'fa-money-bill-wave'); ?>
<?php if (empty($payments)): ?>
<?php uiEmptyState('fa-check-circle', 'No pending cash payments', 'All cash payments have been processed.', null, 'wallet'); ?>
<?php else: ?>
<div class="table-responsive saas-table-wrapper">
<table class="saas-table">
    <thead>
        <tr>
            <th>Reference</th>
            <th>Customer</th>
            <th>Collector</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Evidence</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($payments as $p):
            $ref = $p['receipt_number'];
            $evUrl = paymentEvidenceUrl($p['evidence_url'] ?? null);
            $collectorName = trim(($p['collector_first'] ?? '') . ' ' . ($p['collector_last'] ?? '')) ?: '—';
        ?>
        <tr>
            <td class="font-monospace small"><?= e($ref) ?></td>
            <td><?= e($p['first_name'] . ' ' . $p['last_name']) ?></td>
            <td><?= e($collectorName) ?></td>
            <td class="fw-medium"><?= formatCurrency($p['amount_received'] ?? $p['amount']) ?></td>
            <td class="small"><?= formatDateTime($p['created_at']) ?></td>
            <td>
                <?php if ($evUrl): ?>
                <button type="button" class="btn-saas btn-saas-ghost btn-saas-sm" data-bs-toggle="modal" data-bs-target="#evidenceModal" data-evidence="<?= e($evUrl) ?>" data-ref="<?= e($ref) ?>">
                    <i class="fa-solid fa-image"></i> View
                </button>
                <?php else: ?><span class="text-danger small">Missing</span><?php endif; ?>
            </td>
            <td><?= paymentStatusBadge($p) ?></td>
            <td>
                <div class="d-flex flex-wrap gap-1">
                    <form method="post" action="<?= baseUrl('finance/cash-verify') ?>" class="d-inline" onsubmit="return confirm('Approve this cash payment?')">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="payment_id" value="<?= (int) $p['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn-saas btn-saas-primary btn-saas-sm"><i class="fa-solid fa-check"></i></button>
                    </form>
                    <form method="post" action="<?= baseUrl('finance/cash-verify') ?>" class="d-inline" onsubmit="return confirm('Reject this cash payment?')">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="payment_id" value="<?= (int) $p['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn-saas btn-saas-danger btn-saas-sm"><i class="fa-solid fa-xmark"></i></button>
                    </form>
                    <form method="post" action="<?= baseUrl('finance/cash-verify') ?>" class="d-inline" onsubmit="return confirm('Mark for further review?')">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="payment_id" value="<?= (int) $p['id'] ?>">
                        <input type="hidden" name="action" value="review">
                        <button type="submit" class="btn-saas btn-saas-ghost btn-saas-sm"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                    <a href="<?= baseUrl('api/invoice') ?>&ref=<?= urlencode($p['invoice_number'] ?? $ref) ?>" target="_blank" class="btn-saas btn-saas-ghost btn-saas-sm" title="Invoice"><i class="fa-solid fa-file-invoice"></i></a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
<?php uiGlassCardClose(); ?>

<div class="modal fade" id="evidenceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Payment Evidence — <span id="evidenceRef"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="evidenceImage" src="" alt="Payment evidence" class="img-fluid rounded" style="max-height:70vh">
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('evidenceModal')?.addEventListener('show.bs.modal', (e) => {
    const btn = e.relatedTarget;
    if (!btn) return;
    document.getElementById('evidenceImage').src = btn.dataset.evidence;
    document.getElementById('evidenceRef').textContent = btn.dataset.ref || '';
});
</script>
