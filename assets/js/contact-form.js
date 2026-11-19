(function () {
    'use strict';

    /** Classic form POST with submit guard — works without fetch/AJAX (XAMPP-friendly). */
    function initContactForm() {
        const form = document.getElementById('contactForm');
        if (!form || form.dataset.contactBound === '1') {
            return;
        }
        form.dataset.contactBound = '1';

        const btn = form.querySelector('[type="submit"]');
        if (!btn) {
            return;
        }

        const defaultHtml = btn.innerHTML;

        form.addEventListener('submit', function (e) {
            if (form.dataset.submitting === '1') {
                e.preventDefault();
                return;
            }

            if (!form.checkValidity()) {
                return;
            }

            form.dataset.submitting = '1';
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Sending...';
            btn.classList.add('is-sending');
        });

        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                form.dataset.submitting = '0';
                btn.disabled = false;
                btn.removeAttribute('aria-busy');
                btn.innerHTML = defaultHtml;
                btn.classList.remove('is-sending', 'is-sent');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initContactForm);
})();
