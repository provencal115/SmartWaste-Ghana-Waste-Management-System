<div class="row justify-content-center"><div class="col-md-5">
<div class="auth-card animate-in">
    <div class="auth-card-header"><div class="auth-logo"><img src="<?= e(siteLogo()) ?>" alt="SmartWaste logo" width="56" height="56"></div><h4 class="fw-bold">Reset Password</h4></div>
    <div class="auth-card-body saas-form">
        <form method="POST" action="<?= baseUrl('auth/forgot') ?>"><?= Csrf::field() ?>
            <div class="form-floating-modern"><input type="email" name="email" id="email" placeholder=" " required><label for="email">Email address</label></div>
            <button class="btn-saas btn-saas-primary w-100 justify-content-center"><i class="fa-solid fa-paper-plane"></i> Send Reset Link</button>
        </form>
        <a href="<?= baseUrl('auth/login') ?>" class="d-block text-center mt-3 small"><i class="fa-solid fa-arrow-left me-1"></i> Back to login</a>
    </div>
</div></div></div>
