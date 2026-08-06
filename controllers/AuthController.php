<?php
require_once __DIR__ . '/../includes/Controller.php';

class AuthController extends Controller
{
    public function login(): void
    {
        Auth::guest();
        $this->view('auth/login', [], 'auth');
    }

    public function loginPost(): void
    {
        Auth::guest();
        Csrf::validate();
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $user = UserModel::findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            setFlash('error', 'Invalid email or password. Please check your credentials and try again.');
            redirect('auth/login');
        }
        if (!$user['is_active']) {
            setFlash('error', 'Your account is not activated. Please complete registration or contact an administrator.');
            redirect('auth/login');
        }

        $dashboardRoute = $this->config['dashboard_routes'][$user['role_name']] ?? null;
        if (!$dashboardRoute) {
            setFlash('error', 'Your account role is not configured correctly. Please contact an administrator.');
            redirect('auth/login');
        }

        // Ensure collector profile exists (fixes missing collectors table row after failed seed)
        if ($user['role_name'] === 'collector') {
            CollectorModel::ensureForUser((int)$user['id']);
        }

        Auth::login($user);
        logActivity($user['id'], 'login', 'auth');
        redirect($dashboardRoute);
    }

    public function register(): void
    {
        Auth::guest();
        $plans = PricingModel::plans();
        $zones = ZoneModel::all();
        $this->view('auth/register', compact('plans', 'zones'), 'auth');
    }

    public function registerPost(): void
    {
        Auth::guest();
        Csrf::validate();

        $errors = validatePassword($_POST['password'] ?? '');
        if ($errors) {
            setFlash('error', implode('. ', $errors));
            redirect('auth/register');
        }

        if (($_POST['password'] ?? '') !== ($_POST['password_confirm'] ?? '')) {
            setFlash('error', 'Passwords do not match.');
            redirect('auth/register');
        }

        $email = UserModel::normalizeEmail($_POST['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Please enter a valid email address.');
            redirect('auth/register');
        }

        if (UserModel::isRegisteredEmail($email)) {
            setFlash('error', 'This email is already registered.');
            redirect('auth/register');
        }

        $pending = UserModel::findPendingRegistration($email);
        if ($pending) {
            UserModel::deletePendingRegistration((int) $pending['id']);
        }

        $price = getPrice((int)$_POST['payment_plan_id'], $_POST['bin_size'], !empty($_POST['zone_id']) ? (int)$_POST['zone_id'] : null);
        if ($price === null) {
            setFlash('error', 'Invalid pricing.');
            redirect('auth/register');
        }

        $role = Model::fetchOne("SELECT id FROM roles WHERE name = 'resident'");
        $userId = UserModel::create([
            'role_id' => $role['id'],
            'email' => $email,
            'password_hash' => password_hash($_POST['password'], PASSWORD_BCRYPT),
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'phone' => trim($_POST['phone'] ?? ''),
            'verification_token' => generateToken(),
        ]);

        ResidentModel::create([
            'user_id' => $userId,
            'zone_id' => !empty($_POST['zone_id']) ? (int)$_POST['zone_id'] : null,
            'address' => trim($_POST['address']),
            'city' => trim($_POST['city'] ?? 'Accra'),
            'bin_size' => $_POST['bin_size'],
            'bin_color' => $_POST['owns_existing_bin'] ? 'green' : ($_POST['bin_color'] ?? 'green'),
            'payment_plan_id' => (int)$_POST['payment_plan_id'],
            'service_fee' => $price,
            'owns_existing_bin' => !empty($_POST['owns_existing_bin']) ? 1 : 0,
        ]);

        $_SESSION['pending_user_id'] = $userId;
        $_SESSION['pending_confirmation'] = [
            'bin_size' => $_POST['bin_size'],
            'bin_color' => $_POST['owns_existing_bin'] ? null : ($_POST['bin_color'] ?? 'green'),
            'payment_plan_id' => (int)$_POST['payment_plan_id'],
            'service_fee' => $price,
            'owns_existing_bin' => !empty($_POST['owns_existing_bin']),
        ];
        redirect('auth/confirm');
    }

    public function confirm(): void
    {
        Auth::guest();
        if (empty($_SESSION['pending_user_id'])) redirect('auth/register');
        $confirmation = $_SESSION['pending_confirmation'];
        $plans = PricingModel::plans();
        $this->view('auth/confirm', compact('confirmation', 'plans'), 'auth');
    }

    public function confirmPost(): void
    {
        Csrf::validate();
        $userId = (int)($_SESSION['pending_user_id'] ?? 0);
        if (!$userId) redirect('auth/register');

        UserModel::activate($userId);
        ResidentModel::confirm($userId);
        ResidentModel::assignBin($userId);

        NotificationDispatcher::registrationWelcome($userId);

        $user = UserModel::findById($userId);
        $emailSent = $user ? Mailer::sendWelcomeEmail($user) : false;

        unset($_SESSION['pending_user_id'], $_SESSION['pending_confirmation']);

        if ($emailSent) {
            setFlash('success', 'Registration completed successfully! A welcome email has been sent to your inbox. You can now log in.');
        } else {
            setFlash('success', 'Registration completed successfully. Your welcome email could not be sent at this time. Your account is ready — you can log in now.');
        }
        redirect('auth/login');
    }

    public function logout(): void
    {
        $user = Auth::user();
        if ($user) logActivity($user['id'], 'logout', 'auth');
        Auth::logout();
        redirect('home');
    }

    public function forgot(): void
    {
        Auth::guest();
        $this->view('auth/forgot', [], 'auth');
    }

    public function forgotPost(): void
    {
        Csrf::validate();
        $email = trim($_POST['email'] ?? '');
        if ($email && ($user = UserModel::findByEmail($email))) {
            $token = generateToken();
            UserModel::setResetToken($email, $token);
            NotificationDispatcher::passwordReset($email, $token);
        }
        setFlash('success', 'If the email exists, reset instructions have been sent.');
        redirect('auth/login');
    }

    public function reset(): void
    {
        $this->view('auth/reset', ['token' => $_GET['token'] ?? ''], 'auth');
    }

    public function resetPost(): void
    {
        Csrf::validate();
        $errors = validatePassword($_POST['password'] ?? '');
        if ($errors) {
            setFlash('error', implode('. ', $errors));
            redirect('auth/reset', ['token' => $_POST['token'] ?? '']);
        }
        if (($_POST['password'] ?? '') !== ($_POST['password_confirm'] ?? '')) {
            setFlash('error', 'Passwords do not match.');
            redirect('auth/reset', ['token' => $_POST['token'] ?? '']);
        }
        if (UserModel::resetPassword($_POST['token'], password_hash($_POST['password'], PASSWORD_BCRYPT))) {
            setFlash('success', 'Password reset successfully.');
            redirect('auth/login');
        }
        setFlash('error', 'Invalid or expired reset link.');
        redirect('auth/forgot');
    }
}
