<div class="row justify-content-center">
<div class="col-md-5 col-lg-4">
<div class="auth-card animate-in">
    <div class="auth-card-header">
        <div class="auth-logo"><img src="<?= e(siteLogo()) ?>" alt="SmartWaste logo" width="56" height="56" loading="eager"></div>
        <h4 class="fw-bold mb-1">Welcome back</h4>
        <p class="text-secondary small">Sign in to your SmartWaste account</p>
    </div>
    <div class="auth-card-body">
        <form method="POST" action="<?= baseUrl('auth/login') ?>" class="saas-form" data-validate>
            <?= Csrf::field() ?>
            <div class="form-floating-modern">
                <input type="email" name="email" id="email" placeholder=" " required autocomplete="email">
                <label for="email"><i class="fa-solid fa-envelope me-1"></i> Email address</label>
                <div class="field-error">Email is required</div>
            </div>
            <div class="form-floating-modern">
                <input type="password" name="password" id="password" placeholder=" " required autocomplete="current-password">
                <label for="password"><i class="fa-solid fa-lock me-1"></i> Password</label>
                <div class="field-error">Password is required</div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember">
                    <label class="form-check-label small" for="remember">Remember me</label>
                </div>
                <a href="<?= baseUrl('auth/forgot') ?>" class="small fw-semibold">Forgot password?</a>
            </div>
            <button type="submit" class="btn-saas btn-saas-primary w-100 justify-content-center btn-saas-lg"><i class="fa-solid fa-right-to-bracket"></i> Sign In</button>
        </form>
        <p class="text-center mt-4 mb-0 small text-secondary">Don't have an account? <a href="<?= baseUrl('auth/register') ?>" class="fw-semibold">Register as Resident</a></p>

        <div class="glass-card mt-4 p-3 small" style="border-radius:16px">
            <strong class="d-block mb-3" style="color:var(--color-primary)"><i class="fa-solid fa-flask me-1"></i> Demo accounts <span class="text-muted fw-normal">(password: <code>password</code>)</span></strong>
            <div class="d-flex flex-column gap-2">
                <?php foreach ([
                    ['admin@smartwaste.gh', 'Administrator', 'primary'],
                    ['finance@smartwaste.gh', 'Finance Manager', 'purple'],
                    ['inventory@smartwaste.gh', 'Inventory Manager', 'info'],
                    ['collector@smartwaste.gh', 'Garbage Collector', 'success'],
                ] as [$email, $label, $variant]): ?>
                <button type="button" class="demo-login-btn list-item border-0 px-2 py-2 rounded-3 w-100 text-start" style="background:var(--bg-surface-sunken);cursor:pointer" data-email="<?= e($email) ?>">
                    <span><strong class="small"><?= e($label) ?></strong><br><code class="small text-secondary"><?= e($email) ?></code></span>
                    <span class="status-pill status-<?= $variant ?>"><span class="status-dot"></span>Demo</span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<script>
document.querySelectorAll('.demo-login-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('email').value = btn.dataset.email;
        document.getElementById('password').value = 'password';
        document.getElementById('email').dispatchEvent(new Event('input'));
        document.getElementById('password').dispatchEvent(new Event('input'));
    });
});
</script>
