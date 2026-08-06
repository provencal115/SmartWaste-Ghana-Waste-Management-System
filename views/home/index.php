<?php require __DIR__ . '/../partials/navbar.php'; ?>
<?php
require_once __DIR__ . '/../../includes/images.php';
$img = landingImages();
?>

<!-- ═══════════════════ HERO ═══════════════════ -->
<section class="hero-story hero-story--corporate" id="top">
    <div class="hero-animated-bg" aria-hidden="true"></div>
    <div class="hero-bg-shapes">
        <div class="hero-shape hero-shape-1"></div>
        <div class="hero-shape hero-shape-2"></div>
        <div class="hero-shape hero-shape-3"></div>
    </div>
    <div class="hero-story-grid" aria-hidden="true"></div>
    <div class="floating-bins" aria-hidden="true">
        <?php
        $bins = [['s','green',4,10,0],['m','blue',18,42,0.5],['l','indigo',72,15,1],['s','yellow',85,58,0.2],['m','red',10,68,1.6],['l','green',48,82,0.8],['s','blue',55,28,1.2],['m','yellow',32,75,0.4]];
        $colors = array_merge(binColors(), ['indigo' => '#6366f1']);
        foreach ($bins as [$size,$color,$left,$top,$delay]):
        ?>
        <div class="float-bin size-<?= $size ?>" style="left:<?= $left ?>%;top:<?= $top ?>%;--bin-color:<?= $colors[$color] ?? '#6366f1' ?>;animation-delay:<?= $delay ?>s">
            <div class="bin-lid"></div><div class="bin-body"></div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="container position-relative hero-content">
        <div class="row align-items-center min-vh-100 g-5 py-4">
            <div class="col-lg-6 reveal" data-aos="fade-right">
                <div class="hero-badge mb-4"><i class="fa-solid fa-recycle"></i> Smart Waste Management · Ghana</div>
                <h1 class="display-3 fw-bold mb-4 lh-sm hero-title-corporate">Ghana's complete platform for<br><span class="text-gradient">modern waste collection</span></h1>
                <p class="lead text-secondary mb-4 hero-lead">From Accra to Kumasi — register online, receive your assigned bin, schedule pickups, and let our uniformed Ghanaian collectors handle the rest with Mobile Money payments and real-time tracking.</p>
                <div class="d-flex gap-3 flex-wrap mb-4">
                    <a href="<?= baseUrl('auth/register') ?>" class="btn-saas btn-saas-primary btn-saas-lg"><i class="fa-solid fa-rocket"></i> Get Started</a>
                    <a href="<?= baseUrl('auth/login') ?>" class="btn-saas btn-saas-outline btn-saas-lg"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
                </div>
                <div class="hero-trust-row">
                    <div class="hero-trust-item"><i class="fa-solid fa-house"></i> Residential service</div>
                    <div class="hero-trust-item"><i class="fa-solid fa-truck"></i> GPS fleet</div>
                    <div class="hero-trust-item"><i class="fa-solid fa-mobile-screen"></i> Mobile Money</div>
                </div>
            </div>
            <div class="col-lg-6 reveal" data-aos="fade-left">
                <div class="hero-collage">
                    <img src="<?= e($img['hero_main']) ?>" alt="Zoomlion waste collectors loading bins in a Ghanaian community" class="hero-collage-main" loading="eager">
                    <div class="hero-collage-float truck">
                        <img src="<?= e($img['hero_truck']) ?>" alt="Zoomlion Ghana waste collection truck" loading="lazy">
                    </div>
                    <div class="hero-collage-float collector">
                        <img src="<?= e($img['hero_collector']) ?>" alt="Ghanaian waste collector in reflective uniform with resident" loading="lazy">
                    </div>
                    <div class="hero-collage-float resident">
                        <img src="<?= e($img['hero_resident']) ?>" alt="Ghanaian resident using SmartWaste mobile service" loading="lazy">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <a href="#how-it-works" class="hero-scroll-hint text-decoration-none">
        <span>See how it works</span><i class="fa-solid fa-chevron-down"></i>
    </a>
</section>

<?php uiGhanaBrandBar(); ?>

<!-- ═══════════════════ LIVE STATS ═══════════════════ -->
<section class="landing-section stats-band py-5" id="stats">
    <div class="container">
        <div class="section-header reveal text-center mb-5">
            <span class="section-label">Trusted Across Ghana</span>
            <h2>Real impact, real numbers</h2>
            <p class="text-secondary mb-0 mx-auto" style="max-width:560px">Live statistics from our Smart Waste Management platform serving Ghanaian communities daily</p>
        </div>
        <div class="row g-4">
            <?php foreach ([['fa-users','Registered Residents',$stats['residents']],['fa-recycle','Collections Completed',$stats['collections']],['fa-dumpster','Bins Managed',$stats['bins']],['fa-truck','Fleet Vehicles',$stats['trucks']]] as [$icon,$label,$val]): ?>
            <div class="col-6 col-lg-3 reveal">
                <div class="stat-card-premium glass-card text-center">
                    <div class="stat-icon-wrap"><i class="fa-solid <?= $icon ?>"></i></div>
                    <h2 class="counter fw-bold text-gradient mb-1" data-target="<?= (int)$val ?>">0</h2>
                    <p class="text-secondary mb-0 small fw-medium"><?= $label ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php uiGalleryMosaic($img['story_mosaic']); ?>

<?php uiSectionDivider('How it works'); ?>

<!-- ═══════════════════ HOW OUR SERVICE WORKS ═══════════════════ -->
<section class="workflow-section" id="how-it-works">
    <div class="container">
        <div class="section-header reveal mb-5">
            <span class="section-label">End-to-End Journey</span>
            <h2>How Our Service Works</h2>
            <p>Six simple steps from registration to a completed, rated collection — see exactly how SmartWaste manages your waste</p>
        </div>

        <div class="workflow-timeline">
            <?php
            $steps = [
                [1, 'Resident Registration', $img['step_register'], 'Sign up on your phone or computer in minutes. Select your bin size (Small 120L, Medium 240L, or Large 360L), choose a monthly or quarterly payment plan, and complete registration with instant email confirmation.', ['Mobile & web signup', 'Bin size selection', 'Payment plan', 'Instant confirmation'], 'register', null],
                [2, 'Bin Assignment', $img['step_bins'], 'Our inventory team assigns a colour-coded bin to your account. Each bin is QR-tagged and tracked from warehouse to your doorstep — ready for your first collection.', ['Inventory assignment', 'S / M / L bins', 'Multiple colours', 'QR tracking'], 'bins', 'BIN-M-GR-A7X2'],
                [3, 'Scheduling Collection', $img['step_schedule'], 'Open your dashboard calendar, pick a date and preferred time slot, and confirm. You\'ll receive an SMS and app notification confirming your scheduled pickup.', ['Smart calendar', 'Date & time picker', 'Recurring option', 'Confirmation alert'], 'schedule', null],
                [4, 'Collector Arrives', '', 'On collection day, a uniformed SmartWaste collector arrives at your home. They greet you professionally, verify your assigned bin by QR scan, and collect the correct container while the branded truck waits nearby.', ['Branded uniform', 'Friendly greeting', 'Bin verification', 'Truck on standby'], 'collector', null],
                [5, 'Waste Collection', $img['step_collection'], 'Our fleet moves through your neighbourhood on optimised routes. Collectors use safety equipment, load bins into the truck efficiently, and leave every street clean and tidy.', ['Multi-house route', 'Safety gear', 'Efficient loading', 'Clean streets'], 'default', null],
                [6, 'Service Completed', $img['step_complete'], 'Instant notification: "Collection complete!" Your payment is confirmed, status updates to Completed, and you can leave a 5-star rating to help us maintain excellent service.', ['Push notification', 'Payment confirmed', 'Status: Completed', '5-star rating'], 'complete', null],
            ];
            $stepNum = 0;
            foreach ($steps as [$num, $title, $image, $desc, $tags, $type, $binId]):
                $stepNum++;
                if ($stepNum > 1): ?>
            <div class="workflow-arrow reveal" aria-hidden="true"><i class="fa-solid fa-arrow-down"></i></div>
                <?php endif;

                /* ── Step 2: Bin Assignment — Ghana-focused premium layout ── */
                if ($type === 'bins'): ?>
            <div class="bin-assignment-gh reveal" id="step-bin-assignment">
                <div class="bin-assignment-marker"><span>2</span></div>
                <div class="row g-4 g-lg-5 align-items-center">
                    <div class="col-lg-6">
                        <div class="bin-handover-visual">
                            <img src="<?= e($img['step_bins_ghana_handover']) ?>" alt="SmartWaste Ghana officer in reflective vest handing assigned dustbin to resident at a modern Ghanaian home" class="bin-handover-photo" loading="lazy">
                            <div class="bin-handover-badge"><i class="fa-solid fa-recycle"></i> SmartWaste Ghana</div>
                            <div class="bin-handover-truck"><i class="fa-solid fa-truck"></i> Collection fleet nearby</div>
                            <div class="bin-handover-caption"><i class="fa-solid fa-location-dot"></i> Accra, Ghana</div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <span class="section-label">Step 2</span>
                        <h3 class="bin-assignment-title">Bin Assignment</h3>
                        <p class="bin-assignment-desc">Our inventory team assigns a colour-coded dustbin to every registered resident based on the selected bin size. Each dustbin receives a unique QR Code for identification and tracking from the warehouse to the resident's property, ensuring accurate allocation and efficient waste collection.</p>
                        <div class="bin-assignment-badges">
                            <?php foreach (['QR Code Tracking', 'Colour-Coded Bins', '120L / 240L / 360L Capacity', 'Secure Assignment', 'Warehouse Managed'] as $badge): ?>
                            <span class="bin-assignment-badge"><i class="fa-solid fa-check"></i> <?= e($badge) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="bin-id-demo bin-id-demo-lg"><i class="fa-solid fa-qrcode"></i> Assigned Bin ID: <?= e($binId) ?></div>
                        <div class="wheelie-bin-showcase">
                            <?php foreach ([
                                ['Small Bin', 120, 'green', 'size-s', 0.85],
                                ['Medium Bin', 240, 'blue', 'size-m', 1],
                                ['Large Bin', 360, 'black', 'size-l', 1.15],
                            ] as [$label, $litres, $color, $sizeCls, $scale]): ?>
                            <div class="wheelie-bin-card" style="--bin-scale:<?= $scale ?>">
                                <div class="wheelie-bin-scene">
                                    <div class="wheelie-bin <?= $sizeCls ?>" style="--bin-color:<?= binColors()[$color] ?? '#22c55e' ?>">
                                        <div class="wheelie-lid"><div class="wheelie-lid-handle"></div></div>
                                        <div class="wheelie-body">
                                            <div class="wheelie-grip"></div>
                                            <span class="wheelie-label"><?= $litres ?>L</span>
                                        </div>
                                        <div class="wheelie-wheels"><span></span><span></span></div>
                                        <div class="wheelie-shadow"></div>
                                    </div>
                                </div>
                                <div class="wheelie-bin-info">
                                    <strong><?= e($label) ?></strong>
                                    <span><?= $litres ?>L</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
                <?php continue; endif; ?>
            <div class="workflow-step reveal">
                <div class="workflow-marker"><?= $num ?></div>
                <div class="workflow-visual">
                    <?php if ($type === 'collector'): ?>
                    <div class="workflow-visual-split">
                        <img src="<?= e($img['step_collector']) ?>" alt="Resident greeting collector" loading="lazy">
                        <img src="<?= e($img['step_collector_alt']) ?>" alt="Collector with bin at home" loading="lazy">
                    </div>
                    <span class="workflow-brand-badge">SmartWaste</span>
                    <span class="workflow-truck-badge"><i class="fa-solid fa-truck"></i> Truck parked nearby</span>
                    <?php else: ?>
                    <img src="<?= e($image) ?>" alt="<?= e($title) ?>" loading="lazy">
                    <?php endif; ?>
                    <span class="workflow-visual-badge">Step <?= $num ?></span>

                    <?php if ($type === 'register'): ?>
                    <div class="mock-ui-overlay tl">
                        <div class="mock-title"><i class="fa-solid fa-mobile-screen text-success me-1"></i> Register</div>
                        <div class="text-secondary">Bin: Medium · Plan: Monthly</div>
                    </div>
                    <?php elseif ($type === 'schedule'): ?>
                    <div class="mock-ui-overlay br">
                        <div class="mock-title"><i class="fa-solid fa-bell text-success me-1"></i> Confirmed!</div>
                        <div class="mock-success">Pickup: Sat, 8:00 AM</div>
                    </div>
                    <?php elseif ($type === 'complete'): ?>
                    <div class="mock-ui-overlay tl">
                        <div class="mock-title mock-success"><i class="fa-solid fa-circle-check me-1"></i> Collection Complete</div>
                        <div class="rating-stars-lg">★★★★★</div>
                        <div class="text-secondary mt-1">Payment: GHS 45.00 ✓</div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="workflow-content">
                    <h3><?= e($title) ?></h3>
                    <p><?= e($desc) ?></p>
                    <div class="workflow-tags">
                        <?php foreach ($tags as $tag): ?><span class="workflow-tag"><?= e($tag) ?></span><?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php uiSectionDivider('Fleet & Operations'); ?>

<!-- ═══════════════════ FLEET ═══════════════════ -->
<section class="fleet-section" id="fleet">
    <div class="container">
        <div class="section-header reveal mb-5">
            <span class="section-label">Our Fleet & Operations</span>
            <h2>Professional collection infrastructure</h2>
            <p>Modern vehicles, trained staff in safety uniforms, organised bin inventory, and recycling equipment — delivering reliable waste services daily across Ghana</p>
        </div>
        <div class="fleet-showcase reveal<?= hasFleetOperationsVideo() ? '' : ' fleet-showcase--poster' ?>">
            <div class="fleet-video-wrap" aria-hidden="true">
                <?php if (hasFleetOperationsVideo()): ?>
                <video class="fleet-bg-video"
                       autoplay muted loop playsinline preload="metadata"
                       poster="<?= e($img['fleet_poster']) ?>"
                       data-playback-rate="0.65">
                    <source src="<?= e(fleetOperationsVideo()) ?>" type="video/mp4">
                </video>
                <?php else: ?>
                <img class="fleet-poster-fallback" src="<?= e($img['fleet_poster']) ?>" alt="Ghanaian waste collection — SmartWaste fleet operations" loading="eager" decoding="async">
                <?php endif; ?>
                <div class="fleet-video-scrim"></div>
            </div>
            <div class="fleet-grid fleet-grid-4">
                <?php
                $fleetCards = [
                    [$img['fleet_cards'][0], 'Collection Fleet', 'GPS-enabled trucks serving neighbourhoods with strict maintenance schedules.', 'fa-truck', 'fleet-item-lg'],
                    [$img['fleet_cards'][1], 'Safety Uniforms', 'Trained, branded collectors with full safety equipment.', 'fa-hard-hat', 'fleet-item-sm'],
                    [$img['fleet_cards'][2], 'Bin Inventory', 'Colour-coded S/M/L bins tracked from warehouse to home.', 'fa-dumpster', 'fleet-item-sm'],
                    [$img['fleet_cards'][3], 'Recycling & Sorting', 'Equipment and processes supporting environmentally responsible waste management.', 'fa-recycle', 'fleet-item-lg'],
                ];
                foreach ($fleetCards as [$photo, $title, $desc, $icon, $sizeClass]):
                ?>
                <div class="fleet-item <?= e($sizeClass) ?>">
                    <img src="<?= e($photo) ?>" alt="<?= e($title) ?>" class="fleet-item-photo" loading="lazy">
                    <div class="fleet-item-overlay">
                        <h4><i class="fa-solid <?= e($icon) ?> me-2"></i><?= e($title) ?></h4>
                        <p><?= e($desc) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php uiMidPageCta(
    $img['cta_bg'],
    'Ready for cleaner streets in your community?',
    'Join residents across Ghana who trust SmartWaste for professional collection, Mobile Money payments, and real-time tracking.'
); ?>

<?php uiSectionDivider('Why SmartWaste'); ?>

<!-- ═══════════════════ WHY CHOOSE US ═══════════════════ -->
<section class="why-section" id="why-us">
    <div class="container">
        <div class="section-header reveal mb-5">
            <span class="section-label">Why SmartWaste</span>
            <h2>Why choose us</h2>
        </div>
        <div class="why-grid reveal">
            <?php foreach ([
                ['fa-clock', 'Reliable Collection', 'On-time pickups with optimised routes and GPS tracking'],
                ['fa-tags', 'Affordable Pricing', 'Flexible plans for every household and business'],
                ['fa-bell', 'Smart Notifications', 'Automated reminders so you never miss a collection'],
                ['fa-user-tie', 'Professional Staff', 'Uniformed, trained collectors with great service'],
                ['fa-lock', 'Secure Payments', 'Encrypted Mobile Money, cards, and cash verification'],
                ['fa-leaf', 'Eco-Friendly', 'Recycling programmes and responsible waste disposal'],
                ['fa-headset', 'Fast Support', '24/7 help via app, phone, and email'],
            ] as [$icon, $title, $desc]): ?>
            <div class="why-card"><div class="why-card-icon"><i class="fa-solid <?= $icon ?>"></i></div><h5><?= e($title) ?></h5><p><?= e($desc) ?></p></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php uiSectionDivider('Success Stories'); ?>

<!-- ═══════════════════ CUSTOMER SUCCESS ═══════════════════ -->
<section class="success-section landing-section" id="success">
    <div class="container">
        <div class="section-header reveal mb-5">
            <span class="section-label">Customer Success</span>
            <h2>Communities thriving with SmartWaste</h2>
            <p>Real results from estates and neighbourhoods across Ghana</p>
        </div>
        <div class="row g-4">
            <?php foreach ($img['success_stories'] as $i => [$photo, $location, $metric, $story]): ?>
            <div class="col-md-4 reveal">
                <article class="success-story-card">
                    <div class="success-story-media">
                        <img src="<?= e($photo) ?>" alt="<?= e($location) ?> — SmartWaste Ghana success story" loading="lazy">
                        <span class="success-story-metric"><?= e($metric) ?></span>
                    </div>
                    <div class="success-story-body">
                        <h5 class="fw-bold mb-2"><i class="fa-solid fa-location-dot text-success me-1"></i><?= e($location) ?></h5>
                        <p class="text-secondary small mb-0"><?= e($story) ?></p>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════ TESTIMONIALS ═══════════════════ -->
<section class="landing-section" style="background:var(--bg-surface-sunken)" id="testimonials">
    <div class="container">
        <div class="section-header reveal mb-5">
            <span class="section-label">Testimonials</span>
            <h2>What our customers say</h2>
        </div>
        <div class="row g-4">
            <?php foreach ($img['testimonials'] as [$photo, $name, $role, $text, $stars]): ?>
            <div class="col-md-4 reveal">
                <div class="testimonial-card glass-card h-100">
                    <div class="testimonial-stars mb-3"><?= str_repeat('<i class="fa-solid fa-star"></i>', $stars) ?></div>
                    <p class="testimonial-text">"<?= e($text) ?>"</p>
                    <div class="testimonial-author">
                        <img src="<?= e($photo) ?>" alt="<?= e($name) ?>" class="testimonial-photo" loading="lazy">
                        <div><strong class="small d-block"><?= e($name) ?></strong><span class="text-secondary" style="font-size:0.75rem"><?= e($role) ?></span></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════ FAQ ═══════════════════ -->
<section class="landing-section" id="faq">
    <div class="container">
        <div class="section-header reveal mb-4"><span class="section-label">FAQ</span><h2>Common questions</h2></div>
        <div class="faq-section reveal">
            <?php foreach ([
                ['How do I register?', 'Click Get Started, choose your bin size and payment plan, enter your details, and confirm. Your bin is assigned within 48 hours.'],
                ['How does the collector find my bin?', 'Every bin has a unique QR-coded Bin ID linked to your account. Collectors scan it at pickup.'],
                ['Can I schedule one-time pickups?', 'Yes — use the dashboard calendar for one-time or recurring collections.'],
                ['What payments are accepted?', 'Mobile Money, cards, bank transfer, and verified cash with digital receipts.'],
                ['How do I know collection is done?', 'You receive an instant app/SMS notification with payment confirmation and can leave a 5-star rating.'],
            ] as [$q, $a]): ?>
            <div class="faq-item">
                <button type="button" class="faq-question"><?= e($q) ?> <i class="fa-solid fa-chevron-down"></i></button>
                <div class="faq-answer"><div class="faq-answer-inner"><?= e($a) ?></div></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════ FINAL CTA ═══════════════════ -->
<section class="landing-section pb-5" id="register">
    <div class="container">
        <div class="cta-hero reveal">
            <img src="<?= e($img['cta_bg']) ?>" alt="SmartWaste collection truck in a clean Ghanaian neighbourhood" class="cta-hero-bg" loading="lazy">
            <div class="cta-hero-overlay"></div>
            <div class="cta-hero-content">
                <h2>Start your cleaner, smarter waste journey today</h2>
                <p>Join thousands of residents across Ghana. Register free, get your bin assigned, and schedule your first collection in minutes.</p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="<?= baseUrl('auth/register') ?>" class="btn-saas btn-saas-lg" style="background:#fff;color:#047857!important"><i class="fa-solid fa-user-plus"></i> Register Now — Free</a>
                    <a href="<?= baseUrl('auth/login') ?>" class="btn-saas btn-saas-outline btn-saas-lg" style="border-color:rgba(255,255,255,0.6);color:#fff!important"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
