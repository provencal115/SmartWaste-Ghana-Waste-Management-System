<?php uiPageHeader('Field Reports', 'Report issues during collection'); ?>
<div class="row justify-content-center"><div class="col-lg-7">
<div class="glass-card saas-card animate-in"><div class="saas-card-body saas-form">
    <form method="POST" action="<?= baseUrl('collector/reports') ?>"><?= Csrf::field() ?>
        <label class="form-label">Report Type</label>
        <div class="row g-2 mb-4">
            <?php foreach ([['overflow','Overflowing Bin','fa-fill-drip'],['damaged_bin','Damaged Bin','fa-hammer'],['blocked_road','Blocked Road','fa-road-barrier'],['missed_pickup','Missed Pickup','fa-calendar-xmark'],['truck_breakdown','Truck Breakdown','fa-truck-medical'],['emergency','Emergency','fa-triangle-exclamation']] as [$id,$label,$icon]): ?>
            <div class="col-6 col-md-4"><label class="w-100 mb-0"><input type="radio" name="report_type" value="<?= $id ?>" class="d-none report-type-radio" <?= $id==='overflow'?'checked':'' ?>>
                <div class="select-card-inner text-center py-3"><i class="fa-solid <?= $icon ?> text-success mb-2 d-block"></i><small class="fw-medium"><?= $label ?></small></div></label></div>
            <?php endforeach; ?>
        </div>
        <div class="mb-4"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="5" required placeholder="Describe the issue in detail..."></textarea></div>
        <button class="btn-saas btn-saas-primary w-100 justify-content-center"><i class="fa-solid fa-paper-plane"></i> Submit Report</button>
    </form>
</div></div></div></div>
<script>document.querySelectorAll('.report-type-radio').forEach(r=>r.addEventListener('change',function(){document.querySelectorAll('.select-card-inner').forEach(c=>c.style.borderColor='');this.nextElementSibling.style.borderColor='var(--primary)';}));</script>
