<?php uiPageHeader('Reports & Export', 'Generate and download system reports'); ?>
<div class="glass-card saas-card animate-in"><div class="saas-card-body">
    <p class="text-secondary mb-4">Export comprehensive reports in CSV format</p>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach (['residents'=>'Users','payments'=>'Payments','inventory'=>'Inventory','complaints'=>'Complaints'] as $type=>$label): ?>
        <a href="<?= baseUrl('api/export') ?>&type=<?= $type ?>&format=csv" class="btn-saas btn-saas-outline"><i class="fa-solid fa-file-csv me-1"></i><?= $label ?></a>
        <?php endforeach; ?>
    </div>
</div></div>
