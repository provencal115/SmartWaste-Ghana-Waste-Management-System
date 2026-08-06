<?php
/**
 * Corporate page content — About, FAQ, Contact, team, company info.
 */

/** Whether the current request is the marketing homepage. */
function isMarketingHome(): bool
{
    $url = trim($_GET['url'] ?? '', '/');
    return $url === '' || $url === 'home';
}

/** Link to a homepage section from any page. */
function homeAnchor(string $hash): string
{
    $hash = str_starts_with($hash, '#') ? $hash : '#' . $hash;
    return baseUrl('home') . $hash;
}

/**
 * Company contact & location (Accra, Ghana).
 *
 * @return array<string, mixed>
 */
function companyInfo(): array
{
    return [
        'name'       => 'Smart Waste Management Ghana',
        'tagline'    => 'Professional waste collection & inventory management nationwide',
        'address'    => '14 Independence Avenue, Ridge, Accra, Ghana',
        'phone'      => '+233 20 123 4567',
        'phone_alt'  => '+233 30 294 8800',
        'email'      => 'info@smartwaste.gh',
        'support'    => 'support@smartwaste.gh',
        'emergency'  => '+233 50 911 2456',
        'hours'      => 'Mon – Fri: 8:00 AM – 6:00 PM · Sat: 9:00 AM – 1:00 PM',
        'map_lat'    => 5.6037,
        'map_lng'    => -0.1870,
        'map_embed'  => 'https://maps.google.com/maps?q=5.6037,-0.1870&hl=en&z=15&output=embed',
        'social'     => [
            'facebook'  => 'https://facebook.com/',
            'twitter'   => 'https://twitter.com/',
            'linkedin'  => 'https://linkedin.com/',
            'instagram' => 'https://instagram.com/',
        ],
    ];
}

/**
 * FAQ items for the dedicated FAQ page.
 *
 * @return list<array{0: string, 1: string, 2: string}>
 */
function faqItems(): array
{
    return [
        ['register', 'How do I register?', 'Click Get Started on our homepage, choose your bin size and payment plan, enter your details, and confirm. Your colour-coded bin is assigned within 48 hours and linked to your account with a unique QR code.'],
        ['schedule', 'How do I schedule a pickup?', 'Sign in to your resident dashboard, open the calendar, pick a date and time slot, and confirm. You can schedule one-time or recurring collections and receive SMS or app notifications.'],
        ['bins', 'What bin sizes are available?', 'SmartWaste offers Small (120L), Medium (240L), and Large (360L) wheelie bins. Each bin is colour-coded and QR-tagged for tracking from warehouse to your property.'],
        ['payments', 'How do payments work?', 'Choose a monthly or quarterly plan during registration. Invoices are generated automatically and you can pay via Mobile Money, card, bank transfer, or verified cash with a digital receipt.'],
        ['methods', 'Which payment methods are supported?', 'We accept MTN Mobile Money, Vodafone Cash, AirtelTigo Money, Visa/Mastercard, bank transfer, and verified cash payments recorded by our finance team.'],
        ['missed', 'What happens if I miss my collection day?', 'Reschedule from your dashboard or contact support. Our GPS-routed fleet will attempt collection on your next available slot. You receive alerts before and after each scheduled pickup.'],
        ['complaint', 'How can I report a complaint?', 'Logged-in residents can submit feedback from the dashboard. You may also use our Contact Us form or call our support line. All complaints are tracked until resolved.'],
        ['support', 'How do I contact customer support?', 'Email support@smartwaste.gh, call +233 20 123 4567, or visit our Contact page. Our team responds within one business day, with 24/7 emergency line for urgent spillages.'],
        ['collector', 'How does the collector find my bin?', 'Every assigned bin has a unique QR-coded Bin ID linked to your account. Collectors scan it at pickup to verify the correct container and update your collection status in real time.'],
        ['complete', 'How do I know collection is complete?', 'You receive an instant app or SMS notification when your bin is emptied. Payment is confirmed, status updates to Completed, and you can leave a star rating.'],
    ];
}

/**
 * Management team for About page.
 *
 * @return list<array{photo: string, name: string, role: string, bio: string}>
 */
function teamMembers(): array
{
    return [
        [
            'photo' => img('testimonials/testimonial-kwame.jpg'),
            'name'  => 'Kwame Asante',
            'role'  => 'Managing Director',
            'bio'   => 'Leads SmartWaste Ghana with 12+ years in municipal waste operations across Greater Accra and Ashanti Region.',
        ],
        [
            'photo' => img('testimonials/testimonial-ama.jpg'),
            'name'  => 'Ama Serwaa',
            'role'  => 'Director of Operations',
            'bio'   => 'Oversees fleet routing, collector training, and on-time collection performance for estates and commercial clients.',
        ],
        [
            'photo' => img('testimonials/testimonial-emmanuel.jpg'),
            'name'  => 'Emmanuel Mensah',
            'role'  => 'Head of Customer Experience',
            'bio'   => 'Champions resident satisfaction, Mobile Money billing, and community partnerships nationwide.',
        ],
        [
            'photo' => img('collectors/collector-greeting-resident.jpg'),
            'name'  => 'Abena Osei',
            'role'  => 'Inventory & Logistics Manager',
            'bio'   => 'Manages warehouse stock, bin allocation, and QR tracking from depot to doorstep across all zones.',
        ],
    ];
}

/**
 * Corporate page image map.
 *
 * @return array<string, string>
 */
function corporatePageImages(): array
{
    return [
        'about_hero'  => img('ghana/zoomlion-clean-ghana.jpg'),
        'about_story' => img('ghana/story-operations-tour.jpg'),
        'contact_hero'=> img('collectors/collector-with-resident.jpg'),
        'faq_hero'    => img('hero/hero-banner.jpg'),
    ];
}

/**
 * About page statistics.
 *
 * @return array<string, int>
 */
function companyStats(): array
{
    $ops = operationalStats();

    return [
        'customers'   => $ops['active_customers'],
        'waste_tons'  => $ops['collections_completed'],
        'trucks'      => $ops['fleet_vehicles'],
        'communities' => $ops['communities_served'],
        'years'       => 8,
    ];
}
