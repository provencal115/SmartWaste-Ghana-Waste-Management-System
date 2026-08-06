<?php uiPageHeader('Dustbin Inventory', 'Manage warehouse bins and stock'); ?>
<div class="glass-card saas-card mb-4 animate-in"><div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-plus me-2"></i>Add New Bin</div></div>
<div class="saas-card-body saas-form"><form method="POST" action="<?= baseUrl('inventory/bins') ?>" class="row g-3 align-items-end"><?= Csrf::field() ?>
<div class="col-md-2"><label class="form-label">Size</label><select name="size" class="form-select"><option value="small">Small</option><option value="medium">Medium</option><option value="large">Large</option></select></div>
<div class="col-md-2"><label class="form-label">Color</label><select name="color" class="form-select"><?php foreach (array_keys(binColors()) as $c): ?><option value="<?= $c ?>"><?= ucfirst($c) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Location</label><input name="warehouse_location" class="form-control" value="Warehouse A"></div>
<div class="col-md-2"><button class="btn-saas btn-saas-primary w-100"><i class="fa-solid fa-plus"></i> Add</button></div>
</form></div></div>
<?php uiTableWrapOpen('Search bins...'); ?>
<thead><tr><th>Code</th><th>Size</th><th>Color</th><th>Capacity</th><th>Status</th><th>Location</th></tr></thead>
<tbody><?php foreach ($bins as $b): ?><tr>
<td class="font-monospace small fw-medium"><?= e($b['bin_code']) ?></td><td><?= ucfirst($b['size']) ?></td><td><?= ucfirst($b['color']) ?></td>
<td><?= $b['capacity_liters'] ?>L</td><td><?= statusBadge($b['status']) ?></td><td><?= e($b['warehouse_location']) ?></td></tr><?php endforeach; ?></tbody>
<?php uiTableWrapClose(); ?>
