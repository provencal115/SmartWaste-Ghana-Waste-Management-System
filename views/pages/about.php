<?php require __DIR__ . '/../partials/navbar.php'; ?>

<main class="corp-page">
<?php uiCorpHero(
    'About Smart Waste Management Ghana',
    'Leading Ghana\'s transition to cleaner communities through smart collection, inventory control, and Mobile Money convenience.',
    $images['about_hero'],
    'About Us'
); ?>

<section class="corp-section">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6 reveal">
                <span class="section-label">Company Story</span>
                <h2 class="corp-heading">Built for Ghana, trusted by communities</h2>
                <p class="text-secondary">Smart Waste Management Ghana was founded to solve a growing challenge: unreliable waste collection, poor bin tracking, and limited digital payment options in urban and peri-urban communities.</p>
                <p class="text-secondary mb-4">We combine GPS-routed fleets, QR-coded bin inventory, and Mobile Money billing into one platform — so residents, collectors, and administrators work from the same real-time system.</p>
                <div class="corp-values">
                    <div class="corp-value-item"><i class="fa-solid fa-bullseye"></i><div><strong>Mission</strong><span>Deliver reliable, technology-driven waste services that keep Ghanaian communities clean and healthy.</span></div></div>
                    <div class="corp-value-item"><i class="fa-solid fa-eye"></i><div><strong>Vision</strong><span>A Ghana where every household has accountable, scheduled, and environmentally responsible waste collection.</span></div></div>
                </div>
            </div>
            <div class="col-lg-6 reveal">
                <div class="corp-image-card">
                    <img src="<?= e($images['about_story']) ?>" alt="SmartWaste operations team in Ghana" loading="lazy" class="corp-image-card-img">
                </div>
            </div>
        </div>
        <div class="row g-3 mt-2 reveal">
            <?php foreach ([
                ['fa-leaf', 'Sustainability', 'Recycling partnerships and responsible disposal across all service zones.'],
                ['fa-handshake', 'Integrity', 'Transparent pricing, verified payments, and professional collector conduct.'],
                ['fa-users', 'Community First', 'Cleaner streets and healthier neighbourhoods for every resident we serve.'],
                ['fa-lightbulb', 'Innovation', 'Smart scheduling, live tracking, and data-driven route optimisation.'],
            ] as [$icon, $title, $desc]): ?>
            <div class="col-md-6 col-lg-3">
                <div class="corp-mini-card glass-card h-100">
                    <i class="fa-solid <?= e($icon) ?> corp-mini-icon"></i>
                    <h6 class="fw-bold mb-1"><?= e($title) ?></h6>
                    <p class="small text-secondary mb-0"><?= e($desc) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="corp-section corp-section-alt">
    <div class="container">
        <div class="section-header reveal text-center mb-5">
            <span class="section-label">Our Services</span>
            <h2>Complete waste management solutions</h2>
            <p class="text-secondary mb-0 mx-auto" style="max-width:560px">From doorstep collection to warehouse inventory — one integrated platform</p>
        </div>
        <div class="row g-4">
            <?php foreach ([
                ['fa-house-chimney', 'Residential Waste Collection', 'Scheduled pickups for homes, estates, and compounds across Ghana.'],
                ['fa-building', 'Commercial Waste Collection', 'Tailored plans for offices, shops, schools, and industrial clients.'],
                ['fa-recycle', 'Recycling Services', 'Sorting, composting partnerships, and environmentally responsible processing.'],
                ['fa-dumpster', 'Bin Allocation', 'Colour-coded S/M/L bins with QR tracking from warehouse to property.'],
                ['fa-calendar-check', 'Smart Scheduling', 'One-time and recurring pickups with SMS and app confirmations.'],
                ['fa-chart-line', 'Waste Monitoring', 'Live dashboards, collection proof, and compliance reporting.'],
            ] as [$icon, $title, $desc]): ?>
            <div class="col-md-6 col-lg-4 reveal">
                <article class="corp-service-card glass-card h-100">
                    <div class="corp-service-icon"><i class="fa-solid <?= e($icon) ?>"></i></div>
                    <h5 class="fw-bold"><?= e($title) ?></h5>
                    <p class="text-secondary small mb-0"><?= e($desc) ?></p>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="corp-section">
    <div class="container">
        <div class="section-header reveal text-center mb-5">
            <span class="section-label">Why Choose Us</span>
            <h2>The SmartWaste advantage</h2>
        </div>
        <div class="row g-4">
            <?php foreach ([
                ['fa-clock', 'Reliable Collection', 'On-time pickups with GPS-routed fleets and trained collectors.'],
                ['fa-microchip', 'Smart Technology', 'QR bins, live tracking, digital receipts, and role-based dashboards.'],
                ['fa-user-tie', 'Professional Staff', 'Uniformed, safety-equipped teams serving with pride.'],
                ['fa-seedling', 'Environmentally Friendly', 'Recycling programmes and responsible waste processing.'],
                ['fa-lock', 'Secure Payments', 'Encrypted Mobile Money, cards, and verified cash workflows.'],
                ['fa-headset', 'Excellent Customer Support', 'Responsive help via phone, email, and in-app messaging.'],
            ] as [$icon, $title, $desc]): ?>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="why-card corp-why-card"><div class="why-card-icon"><i class="fa-solid <?= e($icon) ?>"></i></div><h5><?= e($title) ?></h5><p><?= e($desc) ?></p></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="corp-section corp-section-alt stats-band py-5">
    <div class="container">
        <div class="section-header reveal text-center mb-5">
            <span class="section-label">By the Numbers</span>
            <h2>Company statistics</h2>
        </div>
        <div class="row g-4">
            <?php foreach ([
                ['Happy Customers', $stats['customers'], 'fa-users'],
                ['Collections Completed', $stats['waste_tons'], 'fa-recycle'],
                ['Collection Trucks', $stats['trucks'], 'fa-truck'],
                ['Communities Served', $stats['communities'], 'fa-map-location-dot'],
                ['Years of Service', $stats['years'], 'fa-award'],
            ] as $i => [$label, $val, $icon]): ?>
            <div class="col-6 col-md-4 col-lg-2<?= $i === 0 ? ' offset-lg-1' : '' ?> reveal">
                <div class="stat-card-premium glass-card text-center">
                    <div class="stat-icon-wrap"><i class="fa-solid <?= e($icon) ?>"></i></div>
                    <h2 class="counter fw-bold text-gradient mb-1" data-target="<?= (int)$val ?>">0</h2>
                    <p class="text-secondary mb-0 small fw-medium"><?= e($label) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="corp-section">
    <div class="container">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-5 reveal">
                <span class="section-label">Find Us</span>
                <h2 class="corp-heading mb-4">Visit our Accra office</h2>
                <div class="corp-contact-card glass-card">
                    <p class="mb-3"><i class="fa-solid fa-location-dot text-success me-2"></i><strong>Address</strong><br><span class="text-secondary"><?= e($company['address']) ?></span></p>
                    <p class="mb-3"><i class="fa-solid fa-phone text-success me-2"></i><strong>Phone</strong><br><a href="tel:<?= e(preg_replace('/\s+/', '', $company['phone'])) ?>"><?= e($company['phone']) ?></a><br><a href="tel:<?= e(preg_replace('/\s+/', '', $company['phone_alt'])) ?>" class="text-secondary small"><?= e($company['phone_alt']) ?></a></p>
                    <p class="mb-3"><i class="fa-solid fa-envelope text-success me-2"></i><strong>Email</strong><br><a href="mailto:<?= e($company['email']) ?>"><?= e($company['email']) ?></a></p>
                    <p class="mb-0"><i class="fa-solid fa-clock text-success me-2"></i><strong>Business Hours</strong><br><span class="text-secondary"><?= e($company['hours']) ?></span></p>
                </div>
            </div>
            <div class="col-lg-7 reveal">
                <div class="corp-map-card glass-card overflow-hidden h-100">
                    <iframe src="<?= e($company['map_embed']) ?>" title="SmartWaste Ghana office location" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen class="corp-map-iframe"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="corp-section corp-section-alt">
    <div class="container">
        <div class="section-header reveal text-center mb-5">
            <span class="section-label">Leadership</span>
            <h2>Meet our team</h2>
            <p class="text-secondary mb-0">Experienced professionals driving cleaner communities across Ghana</p>
        </div>
        <div class="row g-4">
            <?php foreach ($team as $member): ?>
            <div class="col-md-6 col-lg-3 reveal">
                <article class="corp-team-card glass-card h-100 text-center">
                    <img src="<?= e($member['photo']) ?>" alt="<?= e($member['name']) ?>" class="corp-team-photo" loading="lazy">
                    <h5 class="fw-bold mb-1 mt-3"><?= e($member['name']) ?></h5>
                    <p class="text-success small fw-semibold mb-2"><?= e($member['role']) ?></p>
                    <p class="text-secondary small mb-0"><?= e($member['bio']) ?></p>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="corp-section pb-5">
    <div class="container">
        <div class="corp-cta-banner reveal">
            <div class="corp-cta-content">
                <h2 class="fw-bold mb-2">Ready to join SmartWaste Ghana?</h2>
                <p class="mb-4 opacity-90">Register today, schedule your first collection, or speak with our team.</p>
                <div class="d-flex gap-3 flex-wrap justify-content-center">
                    <a href="<?= baseUrl('auth/register') ?>" class="btn-saas btn-saas-lg" style="background:#fff;color:#047857!important"><i class="fa-solid fa-user-plus"></i> Register Now</a>
                    <a href="<?= baseUrl('contact') ?>" class="btn-saas btn-saas-outline btn-saas-lg" style="border-color:rgba(255,255,255,0.6);color:#fff!important"><i class="fa-solid fa-envelope"></i> Contact Us</a>
                    <a href="<?= baseUrl('auth/login') ?>" class="btn-saas btn-saas-ghost btn-saas-lg" style="color:#fff!important"><i class="fa-solid fa-calendar-check"></i> Schedule Collection</a>
                </div>
            </div>
        </div>
    </div>
</section>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
