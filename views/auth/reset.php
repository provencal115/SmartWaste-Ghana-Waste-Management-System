<div class="row justify-content-center"><div class="col-md-5">
<div class="auth-card animate-in">
    <div class="auth-card-header"><div class="auth-logo"><img src="<?= e(siteLogo()) ?>" alt="SmartWaste logo" width="56" height="56"></div><h4 class="fw-bold">Set New Password</h4><p class="text-secondary small">Choose a strong password for your account</p></div>
    <div class="auth-card-body saas-form">
        <form method="POST" action="<?= baseUrl('auth/reset') ?>" id="resetPasswordForm">
            <?= Csrf::field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <div class="password-field-group mb-3" data-password-group>
                <div class="form-floating-modern mb-0">
                    <input type="password" name="password" id="password" placeholder=" " required autocomplete="new-password" data-password-enhanced>
                    <label for="password"><i class="fa-solid fa-lock me-1"></i> New password</label>
                </div>
            </div>

            <div class="password-field-group mb-4" data-password-confirm-group>
                <div class="form-floating-modern mb-0">
                    <input type="password" name="password_confirm" id="password_confirm" placeholder=" " required autocomplete="new-password" data-password-confirm="password">
                    <label for="password_confirm"><i class="fa-solid fa-lock me-1"></i> Confirm password</label>
                </div>
            </div>

            <button type="submit" class="btn-saas btn-saas-primary w-100 justify-content-center"><i class="fa-solid fa-check"></i> Reset Password</button>
        </form>
        <a href="<?= baseUrl('auth/login') ?>" class="d-block text-center mt-3 small"><i class="fa-solid fa-arrow-left me-1"></i> Back to login</a>
    </div>
</div></div></div>
