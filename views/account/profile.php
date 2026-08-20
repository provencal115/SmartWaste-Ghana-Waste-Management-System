<?php
$roleLabel = $config['roles'][$user['role_name'] ?? ''] ?? ucwords(str_replace('_', ' ', $user['role_name'] ?? ''));
$fullName = formatUserFullName($user);
?>
<?php uiPageHeader('My Profile', 'Manage your profile settings'); ?>

<div class="row justify-content-center g-4">
    <div class="col-lg-6">
        <?php uiGlassCardOpen('Profile Photo', null, 'fa-user-circle'); ?>
        <div class="text-center mb-4">
            <?php uiUserAvatar($user, 'profile-avatar-preview mx-auto', 96); ?>
            <p class="text-secondary small mt-3 mb-0"><?= e($fullName) ?> · <?= e($roleLabel) ?></p>
        </div>
        <form method="post" action="<?= baseUrl('account/profile') ?>" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <div class="mb-3">
                <label for="avatar" class="form-label fw-semibold">Choose a new photo</label>
                <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp" required>
                <div class="form-text">JPG, PNG, or WEBP · Max 2 MB</div>
            </div>
            <button type="submit" class="btn-saas btn-saas-primary w-100">
                <i class="fa-solid fa-upload me-2"></i>Upload Photo
            </button>
        </form>
        <?php uiGlassCardClose(); ?>
    </div>

    <div class="col-lg-6">
        <?php uiGlassCardOpen('Full Name', null, 'fa-id-card'); ?>
        <form method="post" action="<?= baseUrl('account/profile/name') ?>" id="profileNameForm">
            <?= Csrf::field() ?>
            <div class="mb-3">
                <label for="full_name" class="form-label fw-semibold">Full Name</label>
                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($fullName) ?>" required maxlength="201" autocomplete="name">
            </div>
            <button type="submit" class="btn-saas btn-saas-primary w-100">
                <i class="fa-solid fa-floppy-disk me-2"></i>Save Changes
            </button>
        </form>
        <?php uiGlassCardClose(); ?>

        <?php uiGlassCardOpen('Change Password', null, 'fa-lock'); ?>
        <form method="post" action="<?= baseUrl('account/profile/password') ?>" id="profilePasswordForm" class="saas-form">
            <?= Csrf::field() ?>
            <div class="password-field-group mb-3">
                <label for="current_password" class="form-label fw-semibold">Current Password</label>
                <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
            </div>
            <div class="password-field-group mb-3" data-password-group>
                <label for="new_password" class="form-label fw-semibold">New Password</label>
                <input type="password" class="form-control" id="new_password" name="password" required autocomplete="new-password" data-password-enhanced>
            </div>
            <div class="password-field-group mb-3" data-password-confirm-group>
                <label for="password_confirm" class="form-label fw-semibold">Confirm New Password</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required autocomplete="new-password" data-password-confirm="new_password">
            </div>
            <button type="submit" class="btn-saas btn-saas-primary w-100">
                <i class="fa-solid fa-key me-2"></i>Change Password
            </button>
        </form>
        <?php uiGlassCardClose(); ?>
    </div>
</div>
