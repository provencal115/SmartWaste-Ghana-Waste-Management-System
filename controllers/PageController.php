<?php
require_once __DIR__ . '/../includes/Controller.php';
require_once __DIR__ . '/../includes/pages.php';

class PageController extends Controller
{
    private const CONTACT_FLASH_SUCCESS = 'Thank you for contacting Smart Waste Management. Your message has been received successfully. Our team will get back to you as soon as possible.';

    public function about(): void
    {
        $company = companyInfo();
        $stats = companyStats();
        $team = teamMembers();
        $images = corporatePageImages();
        $this->view('pages/about', compact('company', 'stats', 'team', 'images'), 'main');
    }

    public function faq(): void
    {
        $faqs = faqItems();
        $images = corporatePageImages();
        $this->view('pages/faq', compact('faqs', 'images'), 'main');
    }

    public function contact(): void
    {
        $company = companyInfo();
        $images = corporatePageImages();
        $this->view('pages/contact', compact('company', 'images'), 'main');
    }

    public function contactPost(): void
    {
        Csrf::validate();

        $name = mb_substr(strip_tags(trim($_POST['full_name'] ?? '')), 0, 150);
        $email = mb_substr(trim($_POST['email'] ?? ''), 0, 255);
        $phone = mb_substr(strip_tags(trim($_POST['phone'] ?? '')), 0, 30);
        $subject = mb_substr(strip_tags(trim($_POST['subject'] ?? '')), 0, 255);
        $message = mb_substr(strip_tags(trim($_POST['message'] ?? '')), 0, 5000);

        if ($name === '' || $email === '' || $subject === '' || $message === '') {
            setFlash('error', 'Please fill in all required fields.');
            redirect('contact');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Please enter a valid email address.');
            redirect('contact');
        }

        if (!ContactMessageModel::tableExists()) {
            setFlash('error', 'Contact messaging is temporarily unavailable. Please try again later or call our support line.');
            redirect('contact');
        }

        $submission = Csrf::validateSubmission('contact', trim($_POST['submission_token'] ?? ''));
        if ($submission === 'duplicate') {
            setFlash('success', self::CONTACT_FLASH_SUCCESS);
            redirect('contact');
        }
        if ($submission === 'invalid') {
            setFlash('error', 'Your session expired. Please refresh the page and try again.');
            redirect('contact');
        }

        $messageId = ContactMessageModel::create([
            'full_name'  => $name,
            'email'      => strtolower($email),
            'phone'      => $phone ?: null,
            'subject'    => $subject,
            'message'    => $message,
            'ip_address' => clientIp(),
        ]);

        $saved = ContactMessageModel::find($messageId);
        if (!$saved) {
            setFlash('error', 'We couldn\'t send your message right now. Please try again.');
            redirect('contact');
        }

        // Public Contact Us: recipient is always contact_messages.email (visitor — not a registered user).
        $customerConfirmSent = Mailer::sendContactCustomerConfirmation($saved);
        $adminNotifySent = Mailer::sendContactAdminNotification($saved);

        if (!$customerConfirmSent) {
            error_log('[SmartWaste] Contact customer confirmation failed for message #' . $messageId);
        }
        if (!$adminNotifySent) {
            error_log('[SmartWaste] Contact admin notification failed for message #' . $messageId);
        }

        if ($customerConfirmSent) {
            setFlash('success', self::CONTACT_FLASH_SUCCESS . ' A confirmation has been sent to your email.');
        } else {
            setFlash('success', self::CONTACT_FLASH_SUCCESS . ' We could not send a confirmation email right now; our team still received your message.');
        }
        redirect('contact');
    }

    public function privacy(): void
    {
        $company = companyInfo();
        $this->view('pages/privacy', compact('company'), 'main');
    }

    public function terms(): void
    {
        $company = companyInfo();
        $this->view('pages/terms', compact('company'), 'main');
    }
}
