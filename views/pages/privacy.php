<?php require __DIR__ . '/../partials/navbar.php'; ?>

<main class="corp-page">
<?php uiCorpHero(
    'Privacy Policy',
    'How SmartWaste Ghana collects, uses, and protects your personal information.',
    corporatePageImages()['faq_hero'],
    'Privacy Policy'
); ?>

<section class="corp-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 corp-legal reveal">
                <p class="text-secondary">Last updated: <?= date('F j, Y') ?></p>
                <h3 class="h5 fw-bold mt-4">Information we collect</h3>
                <p class="text-secondary">We collect registration details, contact information, payment records, and service usage data necessary to provide waste collection and inventory management services.</p>
                <h3 class="h5 fw-bold mt-4">How we use your data</h3>
                <p class="text-secondary">Your data is used to schedule collections, process payments, assign bins, communicate service updates, and improve platform performance. We do not sell personal information to third parties.</p>
                <h3 class="h5 fw-bold mt-4">Data security</h3>
                <p class="text-secondary">We apply industry-standard encryption, access controls, and secure hosting practices to protect resident and staff information.</p>
                <h3 class="h5 fw-bold mt-4">Contact</h3>
                <p class="text-secondary mb-0">Questions about this policy? Email <a href="mailto:<?= e($company['email']) ?>"><?= e($company['email']) ?></a> or visit our <a href="<?= baseUrl('contact') ?>">Contact page</a>.</p>
            </div>
        </div>
    </div>
</section>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
