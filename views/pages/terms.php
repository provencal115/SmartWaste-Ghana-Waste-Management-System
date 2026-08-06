<?php require __DIR__ . '/../partials/navbar.php'; ?>

<main class="corp-page">
<?php uiCorpHero(
    'Terms & Conditions',
    'Terms governing the use of SmartWaste Ghana services and platform.',
    corporatePageImages()['faq_hero'],
    'Terms & Conditions'
); ?>

<section class="corp-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 corp-legal reveal">
                <p class="text-secondary">Last updated: <?= date('F j, Y') ?></p>
                <h3 class="h5 fw-bold mt-4">Service agreement</h3>
                <p class="text-secondary">By registering for SmartWaste Ghana, you agree to scheduled collection terms, applicable fees, and responsible use of assigned bins and platform features.</p>
                <h3 class="h5 fw-bold mt-4">Payments & billing</h3>
                <p class="text-secondary">Subscription plans, Mobile Money transactions, and verified cash payments are subject to the pricing published at registration and in your resident dashboard.</p>
                <h3 class="h5 fw-bold mt-4">User responsibilities</h3>
                <p class="text-secondary">Residents must provide accurate registration details, place bins for collection on scheduled dates, and report damaged or missing bins promptly.</p>
                <h3 class="h5 fw-bold mt-4">Limitation of liability</h3>
                <p class="text-secondary mb-0">SmartWaste Ghana is not liable for delays caused by force majeure, road closures, or circumstances beyond reasonable operational control. For support, <a href="<?= baseUrl('contact') ?>">contact us</a>.</p>
            </div>
        </div>
    </div>
</section>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
