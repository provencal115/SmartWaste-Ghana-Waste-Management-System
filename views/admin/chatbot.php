<?php
uiPageHeader(
    'AI Virtual Assistant',
    'Manage SmartWaste chatbot knowledge base, FAQs, and conversation statistics',
    '<button type="button" class="btn-saas btn-saas-primary btn-saas-sm" data-bs-toggle="modal" data-bs-target="#chatbotModal"><i class="fa-solid fa-plus"></i> Add Response</button>'
);
?>

<div class="row g-4 mb-4">
    <?php
    uiKpi('Total Messages', $stats['total_messages'], 'fa-comments', 'primary', 'All conversations', 0);
    uiKpi('Messages Today', $stats['messages_today'], 'fa-calendar-day', 'success', date('l, j M Y'), 1);
    uiKpi('Unique Sessions', $stats['unique_sessions'], 'fa-users', 'info', 'Guest & logged-in', 2);
    uiKpi('Active Responses', $stats['enabled_responses'] . ' / ' . $stats['knowledge_total'], 'fa-robot', 'warning', 'Enabled knowledge base', 3);
    ?>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="glass-card saas-card animate-in mb-4">
            <div class="saas-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa-solid fa-brain text-success me-2"></i>Knowledge Base</h5>
            </div>
            <div class="saas-card-body p-0">
                <?php if (empty($knowledge)): ?>
                <?php uiEmptyState('fa-robot', 'No chatbot responses', 'Run the chatbot migration to seed default responses.', null, 'route'); ?>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Uses</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($knowledge as $item): ?>
                        <tr>
                            <td>
                                <strong><?= e($item['title']) ?></strong>
                                <?php if ((int)$item['is_suggestion']): ?>
                                <span class="badge bg-success-subtle text-success ms-1">Suggestion</span>
                                <?php endif; ?>
                                <div class="text-secondary small text-truncate" style="max-width:260px"><?= e($item['keywords']) ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark"><?= e(ucfirst($item['category'])) ?></span></td>
                            <td><?= (int)$item['use_count'] ?></td>
                            <td>
                                <?php if ((int)$item['is_enabled']): ?>
                                <span class="badge bg-success">Enabled</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Disabled</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= baseUrl('admin/chatbot&edit=' . (int)$item['id']) ?>" class="btn-saas btn-saas-ghost btn-saas-sm" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <form method="post" action="<?= baseUrl('admin/chatbot') ?>" class="d-inline">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                        <input type="hidden" name="enabled" value="<?= (int)$item['is_enabled'] ? '0' : '1' ?>">
                                        <button type="submit" class="btn-saas btn-saas-ghost btn-saas-sm" title="<?= (int)$item['is_enabled'] ? 'Disable' : 'Enable' ?>">
                                            <i class="fa-solid fa-<?= (int)$item['is_enabled'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                                        </button>
                                    </form>
                                    <form method="post" action="<?= baseUrl('admin/chatbot') ?>" class="d-inline" onsubmit="return confirm('Delete this response?');">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                        <button type="submit" class="btn-saas btn-saas-ghost btn-saas-sm text-danger" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="glass-card saas-card animate-in mb-4">
            <div class="saas-card-header"><h5 class="mb-0"><i class="fa-solid fa-circle-question text-success me-2"></i>Frequently Asked Questions</h5></div>
            <div class="saas-card-body p-0">
                <?php if (empty($faqs)): ?>
                <p class="text-secondary p-4 mb-0">FAQ data appears as users interact with the chatbot.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($faqs as $faq): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="me-2">
                            <div class="fw-semibold"><?= e($faq['user_question']) ?></div>
                            <?php if (!empty($faq['knowledge_title'])): ?>
                            <small class="text-secondary">Matched: <?= e($faq['knowledge_title']) ?></small>
                            <?php endif; ?>
                        </div>
                        <span class="badge bg-success rounded-pill"><?= (int)$faq['hit_count'] ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="glass-card saas-card animate-in">
            <div class="saas-card-header"><h5 class="mb-0"><i class="fa-solid fa-clock-rotate-left text-success me-2"></i>Recent Conversations</h5></div>
            <div class="saas-card-body p-0">
                <?php if (empty($recent)): ?>
                <p class="text-secondary p-4 mb-0">No chat messages yet.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($recent as $msg): ?>
                    <li class="list-group-item">
                        <div class="small text-secondary mb-1"><?= formatDateTime($msg['created_at']) ?>
                            · <?= e($msg['first_name'] ? trim($msg['first_name'] . ' ' . ($msg['last_name'] ?? '')) : 'Guest') ?>
                        </div>
                        <div class="fw-semibold small"><?= e(mb_strimwidth($msg['user_message'], 0, 80, '…')) ?></div>
                        <div class="text-secondary small"><?= e(mb_strimwidth($msg['bot_response'], 0, 100, '…')) ?></div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="chatbotModal" tabindex="-1" aria-labelledby="chatbotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post" action="<?= baseUrl('admin/chatbot') ?>">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="<?= $editItem ? 'update' : 'create' ?>">
                <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?= (int)$editItem['id'] ?>">
                <?php endif; ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="chatbotModalLabel"><?= $editItem ? 'Edit Response' : 'Add Chatbot Response' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Title</label>
                            <input type="text" name="title" class="form-control" required maxlength="150"
                                   value="<?= e($editItem['title'] ?? '') ?>" placeholder="e.g. Schedule pickup">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category</label>
                            <select name="category" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= e($cat) ?>"<?= ($editItem['category'] ?? '') === $cat ? ' selected' : '' ?>><?= e(ucfirst($cat)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Keywords <span class="text-secondary fw-normal">(comma-separated)</span></label>
                            <input type="text" name="keywords" class="form-control" required
                                   value="<?= e($editItem['keywords'] ?? '') ?>" placeholder="schedule pickup, book collection, request pickup">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Response</label>
                            <textarea name="response" class="form-control" rows="8" required placeholder="Use placeholders: {register_url}, {login_url}, {pricing_table}, {phone}, {contact_url}"><?= e($editItem['response'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Priority</label>
                            <input type="number" name="priority" class="form-control" value="<?= (int)($editItem['priority'] ?? 0) ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_enabled" id="cbEnabled"<?= !isset($editItem) || (int)($editItem['is_enabled'] ?? 1) ? ' checked' : '' ?>>
                                <label class="form-check-label" for="cbEnabled">Enabled</label>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_suggestion" id="cbSuggestion"<?= !empty($editItem['is_suggestion']) ? ' checked' : '' ?>>
                                <label class="form-check-label" for="cbSuggestion">Show as quick suggestion</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-saas btn-saas-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-saas btn-saas-primary"><?= $editItem ? 'Save Changes' : 'Add Response' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editItem): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = new bootstrap.Modal(document.getElementById('chatbotModal'));
    modal.show();
});
</script>
<?php endif; ?>
