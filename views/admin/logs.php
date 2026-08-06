<?php uiPageHeader('Audit Logs', 'System activity trail'); ?>
<?php uiTableWrapOpen('Search logs...'); ?>
<thead><tr><th>Action</th><th>Module</th><th>User</th><th>Time</th></tr></thead>
<tbody><?php foreach ($logs as $l): ?><tr>
<td><span class="fw-medium"><?= e($l['action']) ?></span></td><td><code class="small"><?= e($l['module']) ?></code></td>
<td><?= e(trim(($l['first_name']??'').' '.($l['last_name']??'')) ?: 'System') ?></td>
<td class="text-secondary small"><?= formatDateTime($l['created_at']) ?></td></tr><?php endforeach; ?></tbody>
<?php uiTableWrapClose(); ?>
