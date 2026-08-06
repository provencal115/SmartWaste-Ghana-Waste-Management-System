<?php uiPageHeader('Complaint Management', 'Resolve resident feedback and issues'); ?>
<?php if (empty($complaints)): uiEmptyState('fa-comments', 'No complaints', 'All complaints have been resolved.', null, 'comments'); ?>
<?php else: foreach ($complaints as $c): ?>
<div class="glass-card saas-card mb-3 animate-in"><div class="saas-card-body">
    <div class="d-flex justify-content-between align-items-start mb-3"><div><h6 class="fw-bold mb-1"><?= e($c['subject']) ?></h6><small class="text-secondary"><i class="fa-solid fa-user me-1"></i><?= e($c['first_name'].' '.$c['last_name']) ?> · <?= e($c['category']) ?></small></div><?= statusBadge($c['status']) ?></div>
    <form method="POST" action="<?= baseUrl('admin/complaints') ?>" class="row g-2 saas-form"><?= Csrf::field() ?><input type="hidden" name="complaint_id" value="<?= $c['id'] ?>">
    <div class="col-md-3"><select name="status" class="form-select form-select-sm"><option value="open">Open</option><option value="in_progress">In Progress</option><option value="resolved">Resolved</option></select></div>
    <div class="col-md-7"><input name="resolution_notes" class="form-control form-control-sm" placeholder="Resolution notes..."></div>
    <div class="col-md-2"><button class="btn-saas btn-saas-primary btn-saas-sm w-100">Update</button></div></form>
</div></div><?php endforeach; endif; ?>
