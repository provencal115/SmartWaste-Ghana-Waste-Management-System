<?php
$roleLabel = $config['roles'][$user['role_name'] ?? ''] ?? ucwords(str_replace('_', ' ', $user['role_name'] ?? ''));
?>
<?php uiPageHeader('My Profile', 'Update your profile photo'); ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <?php uiGlassCardOpen('Profile Photo', null, 'fa-user-circle'); ?>
        <div class="text-center mb-4">
            <?php uiUserAvatar($user, 'profile-avatar-preview mx-auto', 96); ?>
            <p class="text-secondary small mt-3 mb-0"><?= e($user['first_name'] . ' ' . $user['last_name']) ?> · <?= e($roleLabel) ?></p>
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
</div>
