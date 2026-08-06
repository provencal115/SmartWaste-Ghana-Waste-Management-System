<div class="row justify-content-center"><div class="col-lg-9 col-xl-8">
<div class="auth-card animate-in">
    <div class="auth-card-header pb-0">
        <div class="auth-logo"><img src="<?= e(siteLogo()) ?>" alt="SmartWaste logo" width="56" height="56" loading="eager"></div>
        <h4 class="fw-bold">Create your account</h4>
        <p class="text-secondary small">Register for professional waste collection in Ghana</p>
        <div class="reg-steps mt-3"><div class="reg-step active"></div><div class="reg-step" id="stepIndicator2"></div><div class="reg-step" id="stepIndicator3"></div></div>
    </div>
    <div class="auth-card-body">
        <form method="POST" action="<?= baseUrl('auth/register') ?>" id="registerForm" class="saas-form">
            <?= Csrf::field() ?>

            <h6 class="fw-bold mb-3"><i class="fa-solid fa-user me-2 text-success"></i>Personal Information</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6"><label class="form-label">First Name</label><input name="first_name" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Last Name</label><input name="last_name" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required autocomplete="email"></div>
                <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" placeholder="+233..." required></div>
                <div class="col-md-6">
                    <div class="password-field-group" data-password-group>
                        <label class="form-label" for="registerPassword">Password</label>
                        <input type="password" name="password" id="registerPassword" class="form-control" data-password-enhanced required autocomplete="new-password">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="password-field-group" data-password-confirm-group>
                        <label class="form-label" for="registerPasswordConfirm">Confirm Password</label>
                        <input type="password" name="password_confirm" id="registerPasswordConfirm" class="form-control" data-password-confirm="registerPassword" required autocomplete="new-password">
                    </div>
                </div>
                <div class="col-md-6"><label class="form-label">Address</label><input name="address" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">City</label><input name="city" value="Accra" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Zone</label>
                    <select name="zone_id" class="form-select" id="zoneSelect"><option value="">Select zone</option>
                    <?php foreach ($zones as $z): ?><option value="<?= $z['id'] ?>"><?= e($z['name']) ?></option><?php endforeach; ?>
                    </select></div>
            </div>

            <h6 class="fw-bold mb-3"><i class="fa-solid fa-dumpster me-2 text-success"></i>Do you already own a garbage bin?</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="bin-ownership-card w-100 mb-0">
                        <input type="radio" name="owns_existing_bin" value="0" checked class="d-none">
                        <div class="select-card-inner text-center py-4">
                            <i class="fa-solid fa-box-open fa-2x text-success mb-2"></i>
                            <strong class="d-block">No — I need a bin</strong>
                            <small class="text-secondary">We will assign a new SmartWaste dustbin</small>
                        </div>
                    </label>
                </div>
                <div class="col-md-6">
                    <label class="bin-ownership-card w-100 mb-0">
                        <input type="radio" name="owns_existing_bin" value="1" class="d-none">
                        <div class="select-card-inner text-center py-4">
                            <i class="fa-solid fa-house-circle-check fa-2x text-primary mb-2"></i>
                            <strong class="d-block">Yes — I have my own bin</strong>
                            <small class="text-secondary">Register using your existing wheelie bin</small>
                        </div>
                    </label>
                </div>
            </div>

            <div id="newBinSection">
                <h6 class="fw-bold mb-2">Choose Your New Bin</h6>

                <div class="bin-preview-panel glass-card p-4 mb-4 text-center" id="binPreviewPanel">
                    <p class="text-secondary small mb-2">Your bin preview</p>
                    <div class="bin-preview-stage mx-auto">
                        <?php uiMiniBin('medium', 'green', 'bin-preview-bin size-m', 'binPreviewBin'); ?>
                    </div>
                    <p class="mb-0 mt-3 fw-semibold" id="binPreviewLabel">Medium · Green</p>
                </div>

                <div class="row g-3 mb-3">
                    <?php foreach (['small'=>120,'medium'=>240,'large'=>360] as $size=>$cap): ?>
                    <div class="col-md-4"><label class="bin-select-card w-100 mb-0">
                        <input type="radio" name="bin_size" value="<?= $size ?>" <?= $size==='medium'?'checked':'' ?> class="d-none bin-size-input">
                        <div class="select-card-inner">
                            <?php uiMiniBin($size, 'green', 'mx-auto mb-2 js-bin-card-preview'); ?>
                        <strong><?= ucfirst($size) ?></strong><small class="d-block text-secondary"><?= $cap ?>L</small></div>
                    </label></div>
                    <?php endforeach; ?>
                </div>
                <h6 class="fw-bold mb-2">Bin Colour</h6>
                <div class="d-flex gap-2 mb-1 flex-wrap align-items-center" id="colorSection">
                    <?php foreach (binColors() as $c=>$hex): ?>
                    <label class="color-swatch" style="background:<?= $hex ?>" title="<?= ucfirst($c) ?>">
                        <input type="radio" name="bin_color" value="<?= $c ?>" <?= $c==='green'?'checked':'' ?> class="d-none bin-color-input">
                    </label>
                    <?php endforeach; ?>
                </div>
                <p class="text-secondary small mb-4">Selected: <strong id="selectedColorName">Green</strong></p>
            </div>

            <div id="existingBinSection" class="d-none">
                <h6 class="fw-bold mb-2">Select Your Existing Bin Size</h6>
                <div class="row g-3 mb-4">
                    <?php foreach (['small'=>120,'medium'=>240,'large'=>360] as $size=>$cap): ?>
                    <div class="col-md-4"><label class="bin-select-card w-100 mb-0">
                        <input type="radio" name="bin_size" value="<?= $size ?>" class="d-none bin-size-input" disabled>
                        <div class="select-card-inner"><div class="mini-bin mx-auto mb-2 size-<?= substr($size,0,1) ?>" style="--bin-color:#3b82f6"></div>
                        <strong><?= ucfirst($size) ?></strong><small class="d-block text-secondary"><?= $cap ?>L</small></div>
                    </label></div>
                    <?php endforeach; ?>
                </div>
                <div class="alert alert-info small"><i class="fa-solid fa-circle-info me-1"></i> No new bin will be assigned. Service charges apply based on your existing bin size.</div>
            </div>

            <h6 class="fw-bold mb-3"><i class="fa-solid fa-credit-card me-2 text-success"></i>Payment Plan</h6>
            <div class="row g-3 mb-4">
                <?php foreach ($plans as $p): ?>
                <div class="col-md-4"><label class="plan-card w-100 mb-0">
                    <input type="radio" name="payment_plan_id" value="<?= $p['id'] ?>" <?= $p['frequency']==='monthly'?'checked':'' ?> class="d-none">
                    <div class="select-card-inner"><strong><?= e($p['name']) ?></strong><small class="d-block text-secondary"><?= e($p['frequency']) ?></small>
                    <div class="price-display text-success fw-bold mt-2" data-plan="<?= $p['id'] ?>">—</div></div>
                </label></div>
                <?php endforeach; ?>
            </div>
            <div class="glass-card p-3 text-center mb-4"><span class="text-secondary">Total Service Charge</span><h3 class="text-gradient fw-bold mb-0" id="totalPrice">GHS 0.00</h3></div>
            <button type="submit" class="btn-saas btn-saas-primary w-100 justify-content-center btn-saas-lg"><i class="fa-solid fa-arrow-right"></i> Continue to Confirmation</button>
        </form>
        <p class="text-center mt-3 mb-0 small"><a href="<?= baseUrl('auth/login') ?>">Already have an account? Sign in</a></p>
    </div>
</div></div></div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registerForm');
    const newSection = document.getElementById('newBinSection');
    const existingSection = document.getElementById('existingBinSection');
    const BIN_COLORS = <?= json_encode(binColors()) ?>;

    const applyBinColor = (hex, colorName, size) => {
        const preview = document.getElementById('binPreviewBin');
        const label = document.getElementById('binPreviewLabel');
        const colorLabel = document.getElementById('selectedColorName');
        const sizeName = (size || 'medium').charAt(0).toUpperCase() + (size || 'medium').slice(1);

        newSection.querySelectorAll('.js-bin-card-preview, .mini-bin').forEach(el => {
            el.classList.add('bin-color-transition');
            el.style.setProperty('--bin-color', hex);
        });

        if (preview) {
            preview.classList.remove('size-s', 'size-m', 'size-l');
            preview.classList.add('size-' + (size || 'medium').charAt(0));
            preview.classList.add('bin-preview-pulse');
            setTimeout(() => preview.classList.remove('bin-preview-pulse'), 400);
        }
        if (label) label.textContent = sizeName + ' · ' + (colorName.charAt(0).toUpperCase() + colorName.slice(1));
        if (colorLabel) colorLabel.textContent = colorName.charAt(0).toUpperCase() + colorName.slice(1);
    };

    const syncBinPreview = () => {
        const color = newSection.querySelector('.bin-color-input:checked')?.value || 'green';
        const size = newSection.querySelector('.bin-size-input:checked')?.value || 'medium';
        applyBinColor(BIN_COLORS[color] || BIN_COLORS.green, color, size);
    };

    const setOwnership = (ownsExisting) => {
        newSection.classList.toggle('d-none', ownsExisting);
        existingSection.classList.toggle('d-none', !ownsExisting);
        document.getElementById('stepIndicator2').classList.add('active');
        document.getElementById('stepIndicator3').classList.add('active');
        newSection.querySelectorAll('.bin-size-input').forEach(i => { i.disabled = ownsExisting; });
        existingSection.querySelectorAll('.bin-size-input').forEach(i => { i.disabled = !ownsExisting; });
        if (ownsExisting) {
            const first = existingSection.querySelector('.bin-size-input');
            if (first) first.checked = true;
        } else {
            const med = newSection.querySelector('[value=medium]');
            if (med) med.checked = true;
            syncBinPreview();
        }
        updatePrice();
    };

    form.querySelectorAll('[name=owns_existing_bin]').forEach(r => {
        r.addEventListener('change', () => setOwnership(r.value === '1' && r.checked));
    });

    newSection.querySelectorAll('.bin-color-input, .bin-size-input').forEach(input => {
        input.addEventListener('change', syncBinPreview);
    });
    syncBinPreview();

    const updatePrice = () => {
        const activeSection = form.querySelector('[name=owns_existing_bin]:checked')?.value === '1' ? existingSection : newSection;
        const size = activeSection.querySelector('.bin-size-input:checked')?.value || 'medium';
        const plan = form.querySelector('[name=payment_plan_id]:checked')?.value;
        const zone = form.querySelector('[name=zone_id]')?.value;
        fetch(`<?= baseUrl('api/pricing') ?>&plan_id=${plan}&bin_size=${size}&zone_id=${zone}`)
            .then(r => r.json()).then(d => {
                const p = (d.price || 0).toFixed(2);
                document.getElementById('totalPrice').textContent = 'GHS ' + p;
                document.querySelectorAll('.price-display').forEach(el => {
                    if (el.dataset.plan === plan) el.textContent = 'GHS ' + p;
                });
            });
    };
    form.addEventListener('change', updatePrice);
    updatePrice();
});
</script>
