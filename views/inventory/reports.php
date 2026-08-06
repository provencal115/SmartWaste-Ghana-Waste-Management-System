<?php uiPageHeader('Inventory Reports', 'Export and analyze stock data'); ?>
<div class="glass-card saas-card mb-4 animate-in"><div class="saas-card-body d-flex flex-wrap gap-2">
    <a href="<?= baseUrl('api/export') ?>&type=inventory&format=csv" class="btn-saas btn-saas-primary"><i class="fa-solid fa-download"></i> Export CSV</a>
</div></div>
<?php uiTableWrapOpen('Filter inventory...'); ?>
<thead><tr><th>Code</th><th>Size</th><th>Color</th><th>Status</th><th>Location</th></tr></thead>
<tbody><?php foreach ($bins as $b): ?><tr><td><?= e($b['bin_code']) ?></td><td><?= ucfirst($b['size']) ?></td><td><?= ucfirst($b['color']) ?></td><td><?= statusBadge($b['status']) ?></td><td><?= e($b['warehouse_location']) ?></td></tr><?php endforeach; ?></tbody>
<?php uiTableWrapClose(); ?>
