<?php uiPageHeader('Reports & Export', 'Generate and download system reports'); ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="glass-card saas-card animate-in h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-chart-line me-2"></i>Analytics Reports</div></div>
            <div class="saas-card-body">
                <p class="text-secondary mb-4">Export operational intelligence KPIs with current filter settings from the Analytics page, or download a full snapshot below.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= baseUrl('admin/analytics') ?>" class="btn-saas btn-saas-primary btn-saas-sm"><i class="fa-solid fa-sliders"></i> Analytics Dashboard</a>
                    <a href="<?= baseUrl('api/analytics/export') ?>&format=csv" class="btn-saas btn-saas-outline btn-saas-sm"><i class="fa-solid fa-file-csv"></i> CSV</a>
                    <a href="<?= baseUrl('api/analytics/export') ?>&format=pdf" class="btn-saas btn-saas-outline btn-saas-sm" target="_blank"><i class="fa-solid fa-file-pdf"></i> PDF</a>
                    <a href="<?= baseUrl('api/analytics/export') ?>&format=xlsx" class="btn-saas btn-saas-outline btn-saas-sm"><i class="fa-solid fa-file-excel"></i> Excel</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="glass-card saas-card animate-in h-100">
            <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-database me-2"></i>Data Exports</div></div>
            <div class="saas-card-body">
                <p class="text-secondary mb-4">Export raw operational data in CSV format</p>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach (['residents'=>'Users','payments'=>'Payments','inventory'=>'Inventory','complaints'=>'Complaints'] as $type=>$label): ?>
                    <a href="<?= baseUrl('api/export') ?>&type=<?= $type ?>&format=csv" class="btn-saas btn-saas-outline btn-saas-sm"><i class="fa-solid fa-file-csv me-1"></i><?= $label ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
