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
}
