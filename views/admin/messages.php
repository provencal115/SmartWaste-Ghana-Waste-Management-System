<?php
$filterAction = baseUrl('admin/messages');
uiPageHeader(
    'Contact Messages',
    'Customer enquiries submitted via the Contact Us page',
    '<button type="button" class="btn-saas btn-saas-outline btn-saas-sm d-none" id="bulkDeleteBtn"><i class="fa-solid fa-trash"></i> Delete Selected</button>'
);
?>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Total Messages', $stats['total'], 'fa-envelope', 'primary', 'All time', 0);
    uiKpi('Unread Messages', $stats['unread'], 'fa-envelope-open', 'danger', 'Status: New', 1);
    uiKpi('Received Today', $stats['today'], 'fa-calendar-day', 'success', date('l, j M Y'), 2);
    ?>
</div>

<div class="glass-card saas-card animate-in mb-4">
    <div class="saas-card-body">
        <form method="get" action="<?= e($filterAction) ?>" class="row g-3 align-items-end">
            <div class="col-md-4 col-lg-3">
                <label class="form-label small fw-semibold">Search</label>
                <input type="search" name="q" class="form-control" placeholder="Name, email, or subject…" value="<?= e($filters['q']) ?>">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    <?php foreach (['new' => 'New', 'read' => 'Read', 'replied' => 'Replied'] as $val => $label): ?>
                    <option value="<?= e($val) ?>"<?= $filters['status'] === $val ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-lg-2">
                <label class="form-label small fw-semibold">From</label>
                <input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="col-md-2 col-lg-2">
                <label class="form-label small fw-semibold">To</label>
                <input type="date" name="date_to" class="form-control" value="<?= e($filters['date_to']) ?>">
            </div>
            <div class="col-md-4 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn-saas btn-saas-primary btn-saas-sm flex-grow-1"><i class="fa-solid fa-filter"></i> Apply</button>
                <a href="<?= baseUrl('admin/messages') ?>" class="btn-saas btn-saas-ghost btn-saas-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($messages)): ?>
<?php uiEmptyState('fa-envelope', 'No contact messages', 'Messages submitted through the Contact Us page will appear here.', null, 'route'); ?>
<?php else: ?>

<form method="post" action="<?= baseUrl('admin/messages') ?>" id="bulkDeleteForm">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="bulk_delete">
</form>

<?php uiTableWrapOpen('Search messages in this table…', true); ?>
<thead>
<tr>
    <th style="width:36px"><input type="checkbox" class="form-check-input" id="selectAllMessages" aria-label="Select all"></th>
    <?= uiSortableTh('ID') ?>
    <?= uiSortableTh('Full Name') ?>
    <?= uiSortableTh('Email') ?>
    <?= uiSortableTh('Phone') ?>
    <?= uiSortableTh('Subject') ?>
    <th>Message Preview</th>
    <?= uiSortableTh('Date Submitted') ?>
    <?= uiSortableTh('Status') ?>
    <th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($messages as $m): ?>
<tr data-message-id="<?= (int) $m['id'] ?>">
    <td><input type="checkbox" class="form-check-input message-select" name="message_ids[]" value="<?= (int) $m['id'] ?>" form="bulkDeleteForm"></td>
    <td><span class="text-secondary">#<?= (int) $m['id'] ?></span></td>
    <td><strong><?= e($m['full_name']) ?></strong></td>
    <td class="text-secondary"><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></td>
    <td class="text-secondary"><?= e($m['phone'] ?: '—') ?></td>
    <td><?= e($m['subject']) ?></td>
    <td class="text-secondary small" style="max-width:220px"><?= e(mb_strimwidth($m['message'], 0, 80, '…')) ?></td>
    <td class="text-secondary small" data-status-cell><?= formatDateTime($m['created_at']) ?></td>
    <td data-status-badge><?= statusBadge($m['status']) ?></td>
    <td>
        <div class="d-flex gap-1 flex-wrap">
            <button type="button" class="btn-saas btn-saas-ghost btn-saas-sm msg-view-btn" data-id="<?= (int) $m['id'] ?>" title="View">
                <i class="fa-solid fa-eye"></i>
            </button>
            <button type="button" class="btn-saas btn-saas-ghost btn-saas-sm msg-reply-btn"
                    data-id="<?= (int) $m['id'] ?>"
                    data-email="<?= e($m['email']) ?>"
                    data-name="<?= e($m['full_name']) ?>"
                    data-subject="<?= e($m['subject']) ?>"
                    title="Reply">
                <i class="fa-solid fa-reply"></i>
            </button>
            <form method="post" action="<?= baseUrl('admin/messages') ?>" class="d-inline msg-delete-form">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="message_id" value="<?= (int) $m['id'] ?>">
                <button type="submit" class="btn-saas btn-saas-ghost btn-saas-sm text-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
            </form>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
<?php uiTableWrapClose(); ?>
<?php endif; ?>

<!-- View Message Modal -->
<div class="modal fade" id="viewMessageModal" tabindex="-1" aria-labelledby="viewMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content glass-card border-0">
            <div class="modal-header border-0 pb-0">
                <div>
                    <span class="section-label">Message Details</span>
                    <h5 class="modal-title fw-bold" id="viewMessageModalLabel">Contact Message</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewMessageBody">
                <div class="text-center py-5 text-secondary"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn-saas btn-saas-ghost" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn-saas btn-saas-primary" id="viewModalReplyBtn"><i class="fa-solid fa-reply"></i> Reply</button>
            </div>
        </div>
    </div>
</div>

<!-- Reply Modal -->
<div class="modal fade" id="replyMessageModal" tabindex="-1" aria-labelledby="replyMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content glass-card border-0">
            <form method="post" action="<?= baseUrl('admin/messages') ?>">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="message_id" id="replyMessageId" value="">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <span class="section-label">Send Reply</span>
                        <h5 class="modal-title fw-bold" id="replyMessageModalLabel">Reply to Customer</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">To</label>
                        <input type="email" class="form-control" id="replyToEmail" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Regarding</label>
                        <input type="text" class="form-control" id="replySubject" readonly>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Your Reply <span class="text-danger">*</span></label>
                        <textarea name="reply_body" id="replyBody" class="form-control" rows="8" required placeholder="Type your response to the customer…"></textarea>
                        <p class="small text-secondary mt-2 mb-0">The reply will be sent via email when SMTP is configured, and saved in the message history.</p>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn-saas btn-saas-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-saas btn-saas-primary"><i class="fa-solid fa-paper-plane"></i> Send Reply</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const viewModal = document.getElementById('viewMessageModal');
    const replyModal = document.getElementById('replyMessageModal');
    const viewBody = document.getElementById('viewMessageBody');
    const replyMessageId = document.getElementById('replyMessageId');
    const replyToEmail = document.getElementById('replyToEmail');
    const replySubject = document.getElementById('replySubject');
    const replyBody = document.getElementById('replyBody');
    const viewModalReplyBtn = document.getElementById('viewModalReplyBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const bulkDeleteForm = document.getElementById('bulkDeleteForm');
    const selectAll = document.getElementById('selectAllMessages');
    let activeMessage = null;

    const esc = (s) => {
        const d = document.createElement('div');
        d.textContent = s ?? '';
        return d.innerHTML;
    };

    const statusBadgeHtml = (status) => {
        const map = { new: 'danger', read: 'info', replied: 'success' };
        const cls = map[status] || 'secondary';
        const label = status.charAt(0).toUpperCase() + status.slice(1);
        return `<span class="status-pill status-${cls}"><span class="status-dot"></span>${label}</span>`;
    };

    const openReply = (msg) => {
        replyMessageId.value = msg.id;
        replyToEmail.value = msg.email;
        replySubject.value = msg.subject;
        replyBody.value = '';
        bootstrap.Modal.getOrCreateInstance(replyModal).show();
    };

    const renderMessage = (msg) => {
        activeMessage = msg;
        let repliesHtml = '';
        if (msg.replies && msg.replies.length) {
            repliesHtml = '<div class="mt-4"><h6 class="fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left me-2 text-success"></i>Reply History</h6>';
            msg.replies.forEach(r => {
                repliesHtml += `<div class="corp-contact-card glass-card mb-2 p-3"><div class="d-flex justify-content-between gap-2 mb-2"><strong class="small">${esc(r.admin_name || 'Administrator')}</strong><span class="small text-secondary">${esc(r.sent_at || '')}</span></div><p class="small mb-0" style="white-space:pre-wrap">${esc(r.reply_body || '')}</p>${r.email_sent == 1 ? '<span class="badge bg-success-subtle text-success mt-2">Email sent</span>' : '<span class="badge bg-warning-subtle text-warning mt-2">Saved only</span>'}</div>`;
            });
            repliesHtml += '</div>';
        }

        viewBody.innerHTML = `
            <div class="row g-3">
                <div class="col-md-6"><label class="small text-secondary">Full Name</label><p class="fw-semibold mb-0">${esc(msg.full_name)}</p></div>
                <div class="col-md-6"><label class="small text-secondary">Status</label><p class="mb-0">${statusBadgeHtml(msg.status)}</p></div>
                <div class="col-md-6"><label class="small text-secondary">Email</label><p class="mb-0"><a href="mailto:${esc(msg.email)}">${esc(msg.email)}</a></p></div>
                <div class="col-md-6"><label class="small text-secondary">Phone</label><p class="mb-0">${esc(msg.phone || '—')}</p></div>
                <div class="col-12"><label class="small text-secondary">Subject</label><p class="fw-semibold mb-0">${esc(msg.subject)}</p></div>
                <div class="col-12"><label class="small text-secondary">Date Submitted</label><p class="mb-0">${esc(msg.created_at)}</p></div>
                <div class="col-12"><label class="small text-secondary">Message</label><div class="corp-contact-card glass-card p-3"><p class="mb-0" style="white-space:pre-wrap">${esc(msg.message)}</p></div></div>
            </div>${repliesHtml}`;

        const row = document.querySelector(`tr[data-message-id="${msg.id}"]`);
        if (row) {
            const badgeCell = row.querySelector('[data-status-badge]');
            if (badgeCell) badgeCell.innerHTML = statusBadgeHtml(msg.status);
        }
    };

    document.querySelectorAll('.msg-view-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            viewBody.innerHTML = '<div class="text-center py-5 text-secondary"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>';
            bootstrap.Modal.getOrCreateInstance(viewModal).show();
            try {
                const res = await fetch(`${window.BASE_URL}admin/messages/view&id=${id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (!res.ok || !data.message) throw new Error(data.error || 'Failed to load');
                renderMessage(data.message);
            } catch (err) {
                viewBody.innerHTML = `<div class="alert alert-danger mb-0">${err.message}</div>`;
            }
        });
    });

    document.querySelectorAll('.msg-reply-btn').forEach(btn => {
        btn.addEventListener('click', () => openReply({
            id: btn.dataset.id,
            email: btn.dataset.email,
            subject: btn.dataset.subject,
            full_name: btn.dataset.name
        }));
    });

    viewModalReplyBtn?.addEventListener('click', () => {
        if (!activeMessage) return;
        bootstrap.Modal.getInstance(viewModal)?.hide();
        openReply(activeMessage);
    });

    document.querySelectorAll('.msg-delete-form').forEach(form => {
        form.addEventListener('submit', e => {
            e.preventDefault();
            confirmDelete('Delete this contact message permanently?', () => form.submit());
        });
    });

    const updateBulkBtn = () => {
        const checked = document.querySelectorAll('.message-select:checked').length;
        if (bulkDeleteBtn) bulkDeleteBtn.classList.toggle('d-none', checked === 0);
    };

    selectAll?.addEventListener('change', () => {
        document.querySelectorAll('.message-select').forEach(cb => { cb.checked = selectAll.checked; });
        updateBulkBtn();
    });

    document.querySelectorAll('.message-select').forEach(cb => cb.addEventListener('change', updateBulkBtn));

    bulkDeleteBtn?.addEventListener('click', () => {
        const count = document.querySelectorAll('.message-select:checked').length;
        if (!count) return;
        confirmDelete(`Delete ${count} selected message(s)? This cannot be undone.`, () => bulkDeleteForm.submit());
    });
});
</script>
