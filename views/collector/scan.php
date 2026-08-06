<?php uiPageHeader('Scan Bin', 'QR code or barcode lookup'); ?>
<div class="row justify-content-center"><div class="col-lg-6">
<div class="glass-card saas-card animate-in">
    <div class="saas-card-body text-center py-4">
        <div class="empty-icon mx-auto mb-3"><i class="fa-solid fa-qrcode"></i></div>
        <p class="text-secondary mb-4">Point camera at bin QR code or enter code manually</p>
        <form method="POST" action="<?= baseUrl('collector/scan') ?>" class="saas-form"><?= Csrf::field() ?>
            <div class="input-group input-group-lg mb-3">
                <span class="input-group-text bg-transparent"><i class="fa-solid fa-barcode"></i></span>
                <input name="code" class="form-control" placeholder="BIN-M-GN-001" value="<?= e($code ?? '') ?>" required>
                <button class="btn-saas btn-saas-primary">Scan</button>
            </div>
        </form>
        <?php if (!empty($bin)): ?>
        <div class="glass-card p-4 mt-3 text-start animate-in">
            <div class="d-flex align-items-center gap-3 mb-3">
                <?php uiMiniBin($bin['size'], $bin['color'], ''); ?>
                <div><h5 class="mb-0 font-monospace"><?= e($bin['bin_code']) ?></h5><small class="text-secondary"><?= ucfirst($bin['size']) ?> · <?= ucfirst($bin['color']) ?></small></div>
            </div>
            <?php if ($bin['first_name']): ?><p class="mb-2"><i class="fa-solid fa-user me-2 text-success"></i><?= e($bin['first_name'] . ' ' . $bin['last_name']) ?></p><?php endif; ?>
            <?= statusBadge($bin['status']) ?>
        </div>
        <?php endif; ?>
    </div>
</div></div></div>
