<?php uiPageHeader('Pricing Configuration', 'Manage service rates by bin and plan'); ?>
<?php uiTableWrapOpen('Search pricing...'); ?>
<thead><tr><th>Bin Size</th><th>Plan</th><th>Zone</th><th>Price (GHS)</th></tr></thead>
<tbody><?php foreach ($pricing as $p): ?><tr>
<td><span class="fw-medium"><?= ucfirst($p['bin_size']) ?></span></td><td><?= e($p['plan_name']) ?></td><td><?= e($p['zone_name']??'All zones') ?></td>
<td><form method="POST" action="<?= baseUrl('finance/pricing') ?>" class="d-flex gap-2 align-items-center"><?= Csrf::field() ?>
<input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="number" step="0.01" name="price" value="<?= $p['price'] ?>" class="form-control form-control-sm" style="width:100px"><button class="btn-saas btn-saas-primary btn-saas-sm"><i class="fa-solid fa-save"></i></button></form></td></tr><?php endforeach; ?></tbody>
<?php uiTableWrapClose(); ?>
