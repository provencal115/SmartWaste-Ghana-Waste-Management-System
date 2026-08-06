<?php uiPageHeader('Schedule Pickup', 'Book a one-time or recurring collection'); ?>
<div class="row justify-content-center"><div class="col-lg-7">
<div class="glass-card saas-card animate-in">
    <div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-calendar-plus me-2"></i>Collection Details</div></div>
    <div class="saas-card-body saas-form">
        <form method="POST" action="<?= baseUrl('resident/schedule') ?>">
            <?= Csrf::field() ?>
            <div class="mb-3"><label class="form-label">Pickup Type</label>
                <select name="schedule_type" class="form-select"><option value="one_time">One-time Pickup</option><option value="recurring">Recurring Pickup</option></select></div>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Preferred Date</label><input type="date" name="preferred_date" class="form-control" required min="<?= date('Y-m-d') ?>"></div>
                <div class="col-md-6"><label class="form-label">Preferred Time</label><input type="time" name="preferred_time" class="form-control"></div>
            </div>
            <div class="mb-3 mt-3"><label class="form-label">Recurrence (if recurring)</label>
                <select name="recurrence_pattern" class="form-select"><option value="">—</option><option value="weekly">Weekly</option><option value="biweekly">Bi-weekly</option><option value="monthly">Monthly</option></select></div>
            <div class="mb-4"><label class="form-label">Notes</label><textarea name="collection_notes" class="form-control" rows="3" placeholder="Gate code, special instructions..."></textarea></div>
            <button type="submit" class="btn-saas btn-saas-primary"><i class="fa-solid fa-check"></i> Schedule Pickup</button>
        </form>
    </div>
</div></div></div>
