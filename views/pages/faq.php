<?php require __DIR__ . '/../partials/navbar.php'; ?>

<main class="corp-page">
<?php uiCorpHero(
    'Frequently Asked Questions',
    'Quick answers about registration, scheduling, payments, bins, and customer support.',
    $images['faq_hero'],
    'FAQ'
); ?>

<section class="corp-section">
    <div class="container">
        <div class="row justify-content-center mb-5 reveal">
            <div class="col-lg-8">
                <div class="faq-search-wrap glass-card">
                    <label for="faqSearch" class="form-label fw-semibold mb-2"><i class="fa-solid fa-magnifying-glass me-2 text-success"></i>Search FAQs</label>
                    <input type="search" id="faqSearch" class="form-control form-control-lg faq-search-input" placeholder="Type a question or keyword…" autocomplete="off">
                    <p class="small text-secondary mb-0 mt-2" id="faqSearchMeta"><?= count($faqs) ?> questions</p>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="faq-section faq-section-page reveal" id="faqList">
                    <?php foreach ($faqs as [$id, $q, $a]): ?>
                    <div class="faq-item" data-faq-id="<?= e($id) ?>" data-faq-text="<?= e(strtolower($q . ' ' . $a)) ?>">
                        <button type="button" class="faq-question"><?= e($q) ?> <i class="fa-solid fa-chevron-down"></i></button>
                        <div class="faq-answer"><div class="faq-answer-inner"><?= e($a) ?></div></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-center text-secondary mt-4 faq-no-results d-none" id="faqNoResults">No matching questions found. <a href="<?= baseUrl('contact') ?>">Contact us</a> for help.</p>
            </div>
        </div>
        <div class="text-center mt-5 reveal">
            <p class="text-secondary mb-3">Still have questions?</p>
            <a href="<?= baseUrl('contact') ?>" class="btn-saas btn-saas-primary"><i class="fa-solid fa-envelope"></i> Contact Support</a>
        </div>
    </div>
</section>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
