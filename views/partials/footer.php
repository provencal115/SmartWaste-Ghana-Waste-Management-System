<footer class="landing-footer">
    <div class="container">
        <div class="row g-4 mb-5">
            <div class="col-lg-4">
                <h5 class="mb-3 d-flex align-items-center gap-2"><span class="brand-icon" style="width:32px;height:32px;font-size:0.875rem"><i class="fa-solid fa-recycle"></i></span> SmartWaste Ghana</h5>
                <p class="small opacity-75">The modern waste collection and inventory platform built for municipalities and private waste companies across Ghana.</p>
                <div class="d-flex gap-2 mt-4">
                    <a href="<?= e(companyInfo()['social']['facebook']) ?>" class="social-link" aria-label="Facebook" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="<?= e(companyInfo()['social']['twitter']) ?>" class="social-link" aria-label="Twitter" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="<?= e(companyInfo()['social']['linkedin']) ?>" class="social-link" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-linkedin-in"></i></a>
                    <a href="<?= e(companyInfo()['social']['instagram']) ?>" class="social-link" aria-label="Instagram" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="mb-3">More</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="<?= baseUrl('about') ?>">About Us</a></li>
                    <li class="mb-2"><a href="<?= baseUrl('faq') ?>">FAQ</a></li>
                    <li class="mb-2"><a href="<?= baseUrl('contact') ?>">Contact Us</a></li>
                    <li class="mb-2"><a href="<?= baseUrl('privacy') ?>">Privacy Policy</a></li>
                    <li class="mb-2"><a href="<?= baseUrl('terms') ?>">Terms &amp; Conditions</a></li>
                    <li class="mb-2"><a href="<?= baseUrl('contact') ?>">Support</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="mb-3">Explore</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="<?= homeAnchor('#how-it-works') ?>">How It Works</a></li>
                    <li class="mb-2"><a href="<?= homeAnchor('#gallery') ?>">Gallery</a></li>
                    <li class="mb-2"><a href="<?= homeAnchor('#fleet') ?>">Fleet</a></li>
                    <li class="mb-2"><a href="<?= homeAnchor('#register') ?>">Get Started</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6 class="mb-3">Contact</h6>
                <p class="small mb-2 opacity-75"><i class="fa-solid fa-envelope me-2" style="color:#34d399"></i><?= e(companyInfo()['email']) ?></p>
                <p class="small mb-2 opacity-75"><i class="fa-solid fa-phone me-2" style="color:#34d399"></i><?= e(companyInfo()['phone']) ?></p>
                <p class="small opacity-75"><i class="fa-solid fa-location-dot me-2" style="color:#34d399"></i><?= e(companyInfo()['address']) ?></p>
            </div>
        </div>
        <hr style="border-color:rgba(255,255,255,0.08);margin:0 0 1.5rem">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <p class="small mb-0 opacity-50">&copy; <?= date('Y') ?> SmartWaste Ghana. All rights reserved.</p>
            <div class="d-flex gap-3 small opacity-50">
                <a href="<?= baseUrl('privacy') ?>">Privacy</a>
                <a href="<?= baseUrl('terms') ?>">Terms</a>
                <a href="<?= baseUrl('contact') ?>">Support</a>
            </div>
        </div>
    </div>
</footer>
