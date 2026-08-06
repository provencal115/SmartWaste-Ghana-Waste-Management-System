<?php uiPageHeader('Feedback & Complaints', 'Rate our service and track your submissions'); ?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="glass-card saas-card animate-in"><div class="saas-card-header"><div class="saas-card-title"><i class="fa-solid fa-star me-2"></i>Submit Feedback</div></div>
        <div class="saas-card-body saas-form">
            <form method="POST" action="<?= baseUrl('resident/feedback') ?>"><?= Csrf::field() ?>
                <div class="mb-3"><label class="form-label">Subject</label><input name="subject" class="form-control" required placeholder="Brief summary"></div>
                <div class="mb-3"><label class="form-label">Category</label>
                    <select name="category" class="form-select"><option value="service">Service Quality</option><option value="billing">Billing</option><option value="missed_pickup">Missed Pickup</option><option value="other">Other</option></select></div>
                <div class="mb-3"><label class="form-label">Rating</label>
                    <div class="d-flex gap-1" id="starRating"><?php for ($i = 1; $i <= 5; $i++): ?><button type="button" class="btn btn-link p-0 star-btn text-warning fs-4" data-val="<?= $i ?>"><i class="fa-solid fa-star"></i></button><?php endfor; ?></div>
                    <input type="hidden" name="rating" id="ratingInput" value="5"></div>
                <div class="mb-4"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4" required></textarea></div>
                <button class="btn-saas btn-saas-primary w-100 justify-content-center"><i class="fa-solid fa-paper-plane"></i> Submit</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-7">
        <?php uiGlassCardOpen('Your Complaints', null, 'fa-comments'); ?>
        <?php if (empty($complaints)): uiEmptyState('fa-comment-slash', 'No complaints', 'Thank you for your feedback!', null, 'comments'); ?>
        <?php else: foreach ($complaints as $c): ?>
        <div class="list-item"><div><strong><?= e($c['subject']) ?></strong><br><small class="text-secondary"><?= e(substr($c['description'], 0, 100)) ?></small></div><?= statusBadge($c['status']) ?></div>
        <?php endforeach; endif; ?>
        <?php uiGlassCardClose(); ?>
    </div>
</div>
<script>document.querySelectorAll('.star-btn').forEach(b=>b.addEventListener('click',()=>document.getElementById('ratingInput').value=b.dataset.val));</script>
