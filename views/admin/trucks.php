<?php uiPageHeader('Fleet Management', count($trucks) . ' vehicles'); ?>
<?php uiTableWrapOpen('Search vehicles...'); ?>
<thead><tr><th>Vehicle ID</th><th>Type</th><th>Capacity</th><th>Location</th><th>Status</th></tr></thead>
<tbody><?php foreach ($trucks as $t):
    $capacityLabel = ((int) ($t['capacity_kg'] ?? 0)) >= 7000 ? 'Large' : 'Medium';
?>
<tr>
    <td class="fw-bold font-monospace"><?= e($t['plate_number']) ?></td>
    <td><?= e($t['model'] ?: 'Garbage Collection Truck') ?></td>
    <td><?= e($capacityLabel) ?> <span class="text-secondary small">(<?= (int) $t['capacity_kg'] ?> kg)</span></td>
    <td><?= e($t['zone_name'] ?? '—') ?></td>
    <td><?= truckStatusBadge($t['status']) ?></td>
</tr>
<?php endforeach; ?></tbody>
<?php uiTableWrapClose(); ?>
