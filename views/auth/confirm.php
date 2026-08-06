<div class="row justify-content-center"><div class="col-md-6 col-lg-5">
<div class="auth-card animate-in text-center">
    <div class="auth-card-header">
        <div class="auth-logo mx-auto"><img src="<?= e(siteLogo()) ?>" alt="SmartWaste logo" width="56" height="56"></div>
        <h4 class="fw-bold">Confirm Registration</h4>
        <p class="text-secondary small">Review your selection before activating</p>
    </div>
    <div class="auth-card-body">
        <?php if (!empty($confirmation['owns_existing_bin'])): ?>
        <div class="mb-3"><span class="status-pill status-info"><i class="fa-solid fa-house-circle-check me-1"></i> Existing bin owner</span></div>
        <?php else: ?>
        <?php uiMiniBin($confirmation['bin_size'], $confirmation['bin_color'] ?? 'green', 'mx-auto mb-4'); ?>
        <?php endif; ?>
        <div class="glass-card p-0 mb-4 text-start overflow-hidden">
            <?php
            $rows = [['Bin Size', ucfirst($confirmation['bin_size']) . ' (' . ['small'=>120,'medium'=>240,'large'=>360][$confirmation['bin_size']] . 'L)']];
            if (empty($confirmation['owns_existing_bin'])) {
                $rows[] = ['Colour', ucfirst($confirmation['bin_color'])];
                $rows[] = ['Bin Assignment', 'New SmartWaste bin will be assigned'];
            } else {
                $rows[] = ['Bin Assignment', 'Using your existing bin — no new bin assigned'];
            }
            $rows[] = ['Service Fee', formatCurrency($confirmation['service_fee'])];
            foreach ($rows as [$l, $v]):
            ?>
            <div class="list-item"><span class="text-secondary"><?= $l ?></span><strong><?= e($v) ?></strong></div>
            <?php endforeach; ?>
            <div class="list-item" style="background:var(--primary-light)"><span class="fw-bold">Total Payable</span><strong class="text-success fs-5"><?= formatCurrency($confirmation['service_fee']) ?></strong></div>
        </div>
        <form method="POST" action="<?= baseUrl('auth/confirm') ?>"><?= Csrf::field() ?>
            <button type="submit" class="btn-saas btn-saas-primary w-100 justify-content-center btn-saas-lg"><i class="fa-solid fa-check"></i> Confirm & Activate</button>
        </form>
    </div>
</div></div></div>
