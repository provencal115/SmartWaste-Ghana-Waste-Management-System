<?php require __DIR__ . '/../partials/navbar.php'; ?>

<main class="corp-page">
<?php uiCorpHero(
    'Contact Us',
    'We\'re here to help with registration, scheduling, billing, and support across Ghana.',
    $images['contact_hero'],
    'Contact Us'
); ?>

<section class="corp-section">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-5 reveal">
                <span class="section-label">Get in Touch</span>
                <h2 class="corp-heading mb-4">We'd love to hear from you</h2>
                <div class="corp-contact-card glass-card mb-4">
                    <p class="mb-3"><i class="fa-solid fa-location-dot text-success me-2"></i><strong>Office Address</strong><br><span class="text-secondary"><?= e($company['address']) ?></span></p>
                    <p class="mb-3"><i class="fa-solid fa-phone text-success me-2"></i><strong>Phone</strong><br><a href="tel:<?= e(preg_replace('/\s+/', '', $company['phone'])) ?>"><?= e($company['phone']) ?></a><br><span class="text-secondary small"><?= e($company['phone_alt']) ?></span></p>
                    <p class="mb-3"><i class="fa-solid fa-envelope text-success me-2"></i><strong>Email</strong><br><a href="mailto:<?= e($company['email']) ?>"><?= e($company['email']) ?></a><br><a href="mailto:<?= e($company['support']) ?>" class="small"><?= e($company['support']) ?></a></p>
                    <p class="mb-3"><i class="fa-solid fa-clock text-success me-2"></i><strong>Business Hours</strong><br><span class="text-secondary"><?= e($company['hours']) ?></span></p>
                    <p class="mb-0"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i><strong>Emergency Line</strong><br><a href="tel:<?= e(preg_replace('/\s+/', '', $company['emergency'])) ?>" class="text-danger fw-semibold"><?= e($company['emergency']) ?></a><br><span class="text-secondary small">Urgent spillages &amp; service outages — 24/7</span></p>
                </div>
                <div class="d-flex gap-2">
                    <?php foreach ($company['social'] as $network => $url): ?>
                    <a href="<?= e($url) ?>" class="social-link" aria-label="<?= e(ucfirst($network)) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-<?= e($network === 'twitter' ? 'x-twitter' : $network) ?>"></i></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-7 reveal">
                <div class="corp-form-card glass-card">
                    <h4 class="fw-bold mb-1">Send us a message</h4>
                    <p class="text-secondary small mb-4">Fill in the form below and our team will respond within one business day.</p>
                    <?php if (!empty($showContactSuccess)): ?>
                    <div class="contact-success-banner mb-4" role="status" aria-live="polite">
                        <div class="contact-success-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></div>
                        <div>
                            <strong class="d-block">Thank you! Your message has been received.</strong>
                            <span class="text-secondary small">Our team will review it and respond shortly.</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <form method="POST" action="<?= baseUrl('contact') ?>" class="saas-form" id="contactForm" novalidate>
                        <?= Csrf::field() ?>
                        <?= Csrf::submissionField('contact') ?>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Full Name <span class="text-danger">*</span></label><input type="text" name="full_name" class="form-control" required autocomplete="name"></div>
                            <div class="col-md-6"><label class="form-label">Email <span class="text-danger">*</span></label><input type="email" name="email" class="form-control" required autocomplete="email"></div>
                            <div class="col-12"><label class="form-label">Phone Number</label><input type="tel" name="phone" class="form-control" placeholder="+233..." autocomplete="tel"></div>
                            <div class="col-12"><label class="form-label">Subject <span class="text-danger">*</span></label><input type="text" name="subject" class="form-control" required></div>
                            <div class="col-12"><label class="form-label">Message <span class="text-danger">*</span></label><textarea name="message" class="form-control" rows="5" required></textarea></div>
                            <div class="col-12">
                                <button type="submit" class="btn-saas btn-saas-primary w-100 justify-content-center btn-saas-lg" id="contactSubmitBtn">
                                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="row mt-5 reveal">
            <div class="col-12">
                <div class="corp-map-card glass-card overflow-hidden">
                    <iframe src="<?= e($company['map_embed']) ?>" title="SmartWaste Ghana office map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen class="corp-map-iframe corp-map-iframe-lg"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>
</main>

<script src="<?= asset('js/contact-form.js') ?>"></script>
<?php require __DIR__ . '/../partials/footer.php'; ?>
