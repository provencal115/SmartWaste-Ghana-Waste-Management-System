<?php uiPageHeader('Financial Reports', 'Export payment and revenue data'); ?>
<div class="glass-card saas-card animate-in"><div class="saas-card-body">
    <div class="empty-icon mx-auto mb-3"><i class="fa-solid fa-chart-pie"></i></div>
    <h5 class="text-center fw-bold">Export Financial Data</h5>
    <p class="text-secondary text-center mb-4">Download payment records for accounting and analysis</p>
    <div class="text-center"><a href="<?= baseUrl('api/export') ?>&type=payments&format=csv" class="btn-saas btn-saas-primary"><i class="fa-solid fa-download"></i> Export Payments CSV</a></div>
</div></div>
