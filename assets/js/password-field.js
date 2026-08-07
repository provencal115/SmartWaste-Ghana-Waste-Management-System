/**
 * SmartWaste — Reusable password field component
 * Toggle visibility, strength meter, requirements checklist, confirm match.
 */
(function () {
    'use strict';

    let initialized = false;

    const REQUIREMENTS = [
        { key: 'length', label: 'At least 8 characters', test: (p) => p.length >= 8 },
        { key: 'upper', label: 'At least one uppercase letter', test: (p) => /[A-Z]/.test(p) },
        { key: 'lower', label: 'At least one lowercase letter', test: (p) => /[a-z]/.test(p) },
        { key: 'number', label: 'At least one number', test: (p) => /[0-9]/.test(p) },
        { key: 'special', label: 'At least one special character', test: (p) => /[^A-Za-z0-9]/.test(p) },
    ];

    const COMMON_PASSWORDS = new Set([
        'password', 'password1', 'password123', '12345678', '123456789', '1234567890',
        'qwerty', 'qwerty123', 'qwertyui', 'admin123', 'letmein', 'welcome', 'welcome1',
        'smartwaste', 'iloveyou', 'abc12345', 'changeme', 'passw0rd', 'football',
    ]);

    function evaluateStrength(password) {
        const met = REQUIREMENTS.filter((r) => r.test(password)).map((r) => r.key);
        const metCount = met.length;
        const lower = password.toLowerCase();

        if (!password) {
            return { level: 0, label: 'Very Weak', score: 0, met };
        }

        if (
            password.length < 4
            || COMMON_PASSWORDS.has(lower)
            || /^(.)\1{5,}$/.test(password)
            || ['12345678', 'abcdefgh'].includes(lower)
        ) {
            return { level: 1, label: 'Very Weak', score: 12, met };
        }

        if (metCount <= 2 || password.length < 6) {
            return { level: 2, label: 'Weak', score: 32, met };
        }

        if (metCount <= 3 || password.length < 8) {
            return { level: 3, label: 'Medium', score: 52, met };
        }

        if (metCount === 4) {
            return { level: 3, label: 'Medium', score: 62, met };
        }

        if (metCount === 5 && password.length < 12) {
            return { level: 4, label: 'Strong', score: 82, met };
        }

        return { level: 5, label: 'Very Strong', score: 100, met };
    }

    function allRequirementsMet(password) {
        return REQUIREMENTS.every((r) => r.test(password));
    }

    function toggleVisibility(input, btn) {
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        btn.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
        btn.setAttribute('aria-pressed', String(!visible));
        const icon = btn.querySelector('i');
        if (icon) {
            icon.className = visible ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
        }
    }

    function wrapWithToggle(input) {
        if (input.closest('.password-field-wrap')) {
            return input.closest('.password-field-wrap');
        }

        input.dataset.passwordToggle = '1';

        const wrap = document.createElement('div');
        wrap.className = 'password-field-wrap';

        const parent = input.parentElement;
        parent.insertBefore(wrap, input);
        wrap.appendChild(input);
        input.classList.add('password-field-input');

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'password-toggle-btn';
        btn.setAttribute('aria-label', 'Show password');
        btn.setAttribute('aria-pressed', 'false');
        btn.innerHTML = '<i class="fa-solid fa-eye" aria-hidden="true"></i>';

        btn.addEventListener('click', () => toggleVisibility(input, btn));
        btn.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleVisibility(input, btn);
            }
        });

        wrap.appendChild(btn);
        return wrap;
    }

    function getPasswordGroup(input) {
        return input.closest('[data-password-group]') || input.closest('.password-field-group');
    }

    function createStrengthPanelMarkup() {
        const panel = document.createElement('div');
        panel.className = 'password-strength-panel is-visible';
        panel.setAttribute('aria-live', 'polite');
        panel.setAttribute('aria-hidden', 'false');
        panel.innerHTML = `
            <div class="password-strength-panel-inner">
                <div class="password-strength-header">
                    <span class="password-strength-text">Password strength: <strong class="password-strength-label"></strong></span>
                </div>
                <div class="password-strength-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="Password strength">
                    <div class="password-strength-fill"></div>
                </div>
                <div class="password-requirements-wrap">
                    <p class="password-requirements-title">Password must contain:</p>
                    <ul class="password-requirements"></ul>
                </div>
            </div>
        `;

        const ul = panel.querySelector('.password-requirements');
        REQUIREMENTS.forEach((r) => {
            const li = document.createElement('li');
            li.className = 'password-requirement';
            li.dataset.req = r.key;
            li.innerHTML = `<span class="req-icon" aria-hidden="true">○</span><span>${r.label}</span>`;
            ul.appendChild(li);
        });

        return panel;
    }

    function buildStrengthPanel(input) {
        if (input.dataset.strengthPanelBound === '1') {
            return;
        }
        input.dataset.strengthPanelBound = '1';

        const group = getPasswordGroup(input) || input.parentElement;
        if (!group) {
            return;
        }

        let panel = null;

        const removePanel = () => {
            if (!panel) {
                return;
            }
            panel.remove();
            panel = null;
            input.dataset.strengthMet = '0';
        };

        const ensurePanel = () => {
            if (panel) {
                return panel;
            }

            panel = createStrengthPanelMarkup();
            panel.classList.add('is-revealing');
            group.appendChild(panel);
            panel.addEventListener('animationend', () => {
                panel?.classList.remove('is-revealing');
            }, { once: true });
            return panel;
        };

        const renderStrength = (val) => {
            const activePanel = ensurePanel();
            const { level, label, score } = evaluateStrength(val);
            const labelEl = activePanel.querySelector('.password-strength-label');
            const fill = activePanel.querySelector('.password-strength-fill');
            const bar = activePanel.querySelector('.password-strength-bar');

            labelEl.textContent = label;
            fill.style.width = `${score}%`;
            bar.setAttribute('aria-valuenow', String(score));
            activePanel.dataset.level = String(level);

            activePanel.querySelectorAll('.password-requirement').forEach((li) => {
                const req = REQUIREMENTS.find((r) => r.key === li.dataset.req);
                const ok = req && req.test(val);
                li.classList.toggle('is-met', !!ok);
                li.querySelector('.req-icon').textContent = ok ? '✓' : '○';
            });

            input.dataset.strengthMet = allRequirementsMet(val) ? '1' : '0';
        };

        const update = () => {
            const val = input.value;

            if (!val.length) {
                removePanel();
                return;
            }

            renderStrength(val);
        };

        input.addEventListener('input', update);
        input.addEventListener('change', update);
        input.addEventListener('focus', () => {
            if (input.value.length && panel) {
                panel.classList.add('is-active');
            }
        });
        input.addEventListener('blur', () => {
            panel?.classList.remove('is-active');
        });

        removePanel();
    }

    function buildMatchFeedback(confirmInput, passwordInput) {
        const group = confirmInput.closest('[data-password-confirm-group]')
            || confirmInput.closest('.password-field-group')
            || confirmInput.parentElement;

        if (!group || group.querySelector('.password-match-feedback')) {
            return;
        }

        const feedback = document.createElement('div');
        feedback.className = 'password-match-feedback';
        feedback.setAttribute('aria-live', 'polite');
        group.appendChild(feedback);

        const update = () => {
            const pw = passwordInput.value;
            const cf = confirmInput.value;

            if (!cf) {
                feedback.className = 'password-match-feedback';
                feedback.textContent = '';
                confirmInput.dataset.matchOk = '';
                return;
            }

            if (pw === cf && pw.length > 0) {
                feedback.className = 'password-match-feedback is-match';
                feedback.innerHTML = '<span class="match-icon" aria-hidden="true">✓</span> Passwords match';
                confirmInput.dataset.matchOk = '1';
            } else {
                feedback.className = 'password-match-feedback is-mismatch';
                feedback.innerHTML = '<span class="match-icon" aria-hidden="true">✕</span> Passwords do not match';
                confirmInput.dataset.matchOk = '0';
            }
        };

        confirmInput.addEventListener('input', update);
        passwordInput.addEventListener('input', update);
        update();
    }

    function resolvePasswordInput(confirmInput) {
        const targetId = confirmInput.dataset.passwordConfirm;
        if (targetId) {
            return document.getElementById(targetId);
        }
        return confirmInput.form?.querySelector('[name="password"]') || null;
    }

    function showFormError(form, message) {
        let err = form.querySelector('.password-form-error');
        if (!err) {
            err = document.createElement('div');
            err.className = 'password-form-error';
            err.setAttribute('role', 'alert');
            const submit = form.querySelector('[type="submit"]');
            if (submit) {
                submit.parentElement.insertBefore(err, submit);
            } else {
                form.appendChild(err);
            }
        }
        err.textContent = message;
        err.classList.add('is-visible');
    }

    function initFormHandlers() {
        document.querySelectorAll('form').forEach((form) => {
            if (form.dataset.passwordFormBound === '1') {
                return;
            }
            form.dataset.passwordFormBound = '1';

            const password = form.querySelector('[name="password"]');
            const confirm = form.querySelector('[name="password_confirm"]');
            const enhanced = form.querySelector('[data-password-enhanced]');

            if (!password && !confirm) {
                return;
            }

            form.addEventListener('submit', (e) => {
                form.querySelector('.password-form-error')?.classList.remove('is-visible');

                let message = '';

                if (enhanced && password && !allRequirementsMet(password.value)) {
                    message = 'Please meet all password requirements before continuing.';
                    getPasswordGroup(password)?.classList.add('is-invalid');
                }

                if (confirm && password && password.value !== confirm.value) {
                    message = message || 'Passwords do not match.';
                    confirm.closest('[data-password-confirm-group], .password-field-group')?.classList.add('is-invalid');
                }

                if (message) {
                    e.preventDefault();
                    showFormError(form, message);
                    if (password && password.value) {
                        password.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            });

            form.querySelectorAll('input').forEach((field) => {
                field.addEventListener('input', () => {
                    field.closest('.password-field-group, [data-password-confirm-group], .form-floating-modern')
                        ?.classList.remove('is-invalid');
                    form.querySelector('.password-form-error')?.classList.remove('is-visible');
                });
            });
        });
    }

    function init() {
        if (initialized) {
            return;
        }
        initialized = true;

        document.querySelectorAll('input[type="password"]').forEach((input) => {
            wrapWithToggle(input);
        });

        document.querySelectorAll('[data-password-enhanced]').forEach((input) => {
            wrapWithToggle(input);
            buildStrengthPanel(input);
        });

        document.querySelectorAll('[name="password_confirm"], [data-password-confirm]').forEach((confirmInput) => {
            wrapWithToggle(confirmInput);
            const passwordInput = resolvePasswordInput(confirmInput);
            if (passwordInput) {
                buildMatchFeedback(confirmInput, passwordInput);
            }
        });

        initFormHandlers();
    }

    window.SmartWastePassword = {
        init,
        evaluateStrength,
        allRequirementsMet,
        REQUIREMENTS,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
