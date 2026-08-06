<?php
$currentRoute = trim($_GET['url'] ?? '', '/');
$isHome = isMarketingHome();
$sectionLink = static fn (string $hash): string => $isHome ? $hash : homeAnchor($hash);
?>
<nav class="navbar navbar-expand-lg fixed-top glass-nav">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= baseUrl('home') ?>">
            <img src="<?= e(siteLogo()) ?>" alt="SmartWaste logo" class="brand-logo-img" width="36" height="36" loading="eager">
            Smart<span class="text-gradient">Waste</span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#landingNav" aria-label="Menu">
            <i class="fa-solid fa-bars fa-lg"></i>
        </button>
        <div class="collapse navbar-collapse" id="landingNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-0">
                <li class="nav-item"><a class="nav-link px-3" href="<?= e($sectionLink('#how-it-works')) ?>">How It Works</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="<?= e($sectionLink('#gallery')) ?>">Gallery</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="<?= e($sectionLink('#fleet')) ?>">Fleet</a></li>
                <li class="nav-item dropdown nav-dropdown-modern">
                    <a class="nav-link dropdown-toggle px-3<?= in_array($currentRoute, ['about', 'faq', 'contact'], true) ? ' active' : '' ?>"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" id="aboutUsDropdown">
                        About Us
                    </a>
                    <ul class="dropdown-menu dropdown-menu-modern dropdown-menu-end shadow-lg border-0" aria-labelledby="aboutUsDropdown">
                        <li><a class="dropdown-item<?= $currentRoute === 'about' ? ' active' : '' ?>" href="<?= baseUrl('about') ?>"><i class="fa-solid fa-building me-2 text-success"></i>About Us</a></li>
                        <li><a class="dropdown-item<?= $currentRoute === 'faq' ? ' active' : '' ?>" href="<?= baseUrl('faq') ?>"><i class="fa-solid fa-circle-question me-2 text-success"></i>FAQ</a></li>
                        <li><a class="dropdown-item<?= $currentRoute === 'contact' ? ' active' : '' ?>" href="<?= baseUrl('contact') ?>"><i class="fa-solid fa-envelope me-2 text-success"></i>Contact Us</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link px-3" href="<?= e($sectionLink('#testimonials')) ?>">Testimonials</a></li>
                <li class="nav-item">
                    <button type="button" class="topbar-btn mx-1" id="themeToggleLanding" aria-label="Theme"><i class="fa-solid fa-moon"></i></button>
                </li>
                <li class="nav-item ms-lg-2"><a class="btn-saas btn-saas-ghost btn-saas-sm" href="<?= baseUrl('auth/login') ?>">Login</a></li>
                <li class="nav-item ms-lg-1"><a class="btn-saas btn-saas-primary btn-saas-sm" href="<?= baseUrl('auth/register') ?>"><i class="fa-solid fa-arrow-right"></i> Get Started</a></li>
            </ul>
        </div>
    </div>
</nav>
