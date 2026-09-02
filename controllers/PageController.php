<?php
require_once __DIR__ . '/../includes/Controller.php';
require_once __DIR__ . '/../includes/pages.php';

class PageController extends Controller
{
    private const CONTACT_SUCCESS_TITLE = 'Message sent successfully!';
    private const CONTACT_SUCCESS_MESSAGE = 'Thank you for contacting us. Your message has been received successfully. Our team will review it and respond shortly.';

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
        $showContactSuccess = isset($_GET['sent']);
        $this->view('pages/contact', compact('company', 'images', 'showContactSuccess'), 'main');
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
            $this->contactSubmissionError('Please fill in all required fields.');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->contactSubmissionError('Please enter a valid email address.');
            return;
        }

        if (!ContactMessageModel::tableExists()) {
            $this->contactSubmissionError('Contact messaging is temporarily unavailable. Please try again later or call our support line.');
            return;
        }

        $submission = Csrf::validateSubmission('contact', trim($_POST['submission_token'] ?? ''));
        if ($submission === 'duplicate') {
            $this->finishContactSuccess(null, true);
            return;
        }
        if ($submission === 'invalid') {
            $this->contactSubmissionError('Your session expired. Please refresh the page and try again.');
            return;
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
            $this->contactSubmissionError('We couldn\'t send your message right now. Please try again.');
            return;
        }

        $this->finishContactSuccess($saved, false);
    }

    private function contactSubmissionError(string $message): void
    {
        if (isAjax()) {
            jsonResponse(['success' => false, 'message' => $message], 422);
        }

        setFlash('error', $message);
        redirect('contact');
    }

    private function finishContactSuccess(?array $saved, bool $duplicate): void
    {
        $redirectUrl = baseUrl('contact') . '&sent=1';

        if (isAjax()) {
            $payload = [
                'success'  => true,
                'title'    => self::CONTACT_SUCCESS_TITLE,
                'message'  => self::CONTACT_SUCCESS_MESSAGE,
                'redirect' => $redirectUrl,
                'duplicate'=> $duplicate,
            ];

            http_response_code(200);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
            $this->flushResponseAndSendContactEmails($saved);

            return;
        }

        setFlashStructured('success', self::CONTACT_SUCCESS_TITLE, self::CONTACT_SUCCESS_MESSAGE);
        header('Location: ' . $redirectUrl);
        $this->flushResponseAndSendContactEmails($saved);
    }

    private function flushResponseAndSendContactEmails(?array $saved): void
    {
        if (function_exists('fastcgi_finish_request')) {
            session_write_close();
            fastcgi_finish_request();
        } elseif (function_exists('session_write_close')) {
            session_write_close();
        }

        if ($saved) {
            $this->sendContactEmails($saved);
        }

        exit;
    }

    private function sendContactEmails(array $saved): void
    {
        try {
            Mailer::sendContactCustomerConfirmation($saved);
        } catch (Throwable $e) {
            error_log('[SmartWaste] Contact customer confirmation failed: ' . $e->getMessage());
        }

        try {
            Mailer::sendContactAdminNotification($saved);
        } catch (Throwable $e) {
            error_log('[SmartWaste] Contact admin notification failed: ' . $e->getMessage());
        }
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
