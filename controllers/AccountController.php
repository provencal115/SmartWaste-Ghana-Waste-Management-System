<?php
require_once __DIR__ . '/../includes/Controller.php';

class AccountController extends Controller
{
    public function profile(): void
    {
        $user = Auth::requireLogin();
        $this->view('account/profile', compact('user'));
    }

    public function profilePost(): void
    {
        $user = Auth::requireLogin();
        $this->validateCsrf();

        $result = saveUserAvatarUpload((int) $user['id'], $_FILES['avatar'] ?? []);
        if (!$result['ok']) {
            setFlash('error', $result['error'] ?? 'Upload failed.');
            redirect('account/profile');
        }

        if (!UserModel::updateAvatar((int) $user['id'], $result['path'])) {
            setFlash('error', 'Could not save profile photo. Please contact support.');
            redirect('account/profile');
        }

        logActivity((int) $user['id'], 'update', 'profile', ['avatar' => true]);
        setFlash('success', 'Profile photo updated successfully.');
        redirect('account/profile');
    }

    public function namePost(): void
    {
        $user = Auth::requireLogin();
        $this->validateCsrf();

        $parsed = parseFullName($_POST['full_name'] ?? '');
        if (!$parsed['ok']) {
            setFlash('error', $parsed['error'] ?? 'Please enter a valid name.');
            redirect('account/profile');
        }

        $userId = (int) $user['id'];
        if (!UserModel::updateName($userId, $parsed['first_name'], $parsed['last_name'])) {
            setFlash('error', 'Could not update your name. Please try again.');
            redirect('account/profile');
        }

        Auth::updateSessionName($parsed['first_name'], $parsed['last_name']);
        logActivity($userId, 'update', 'profile', ['name' => true]);
        setFlash('success', 'Your profile name has been updated successfully.');
        redirect('account/profile');
    }

    public function passwordPost(): void
    {
        $user = Auth::requireLogin();
        $this->validateCsrf();

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['password_confirm'] ?? '';

        if ($currentPassword === '') {
            setFlash('error', 'Please enter your current password.');
            redirect('account/profile');
        }

        if ($newPassword !== $confirmPassword) {
            setFlash('error', 'Passwords do not match.');
            redirect('account/profile');
        }

        $freshUser = UserModel::findById((int) $user['id']);
        if (!$freshUser || !password_verify($currentPassword, $freshUser['password_hash'])) {
            setFlash('error', 'Current password is incorrect.');
            redirect('account/profile');
        }

        $errors = validatePassword($newPassword);
        if ($errors) {
            setFlash('error', 'Password requirements: ' . implode(', ', $errors));
            redirect('account/profile');
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        if (!UserModel::updatePasswordHash((int) $user['id'], $hash)) {
            setFlash('error', 'Could not update your password. Please try again.');
            redirect('account/profile');
        }

        logActivity((int) $user['id'], 'update', 'profile', ['password' => true]);
        setFlash('success', 'Your password has been changed successfully.');
        redirect('account/profile');
    }
}
