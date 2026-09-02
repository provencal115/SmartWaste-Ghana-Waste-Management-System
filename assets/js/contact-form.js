(function () {
    'use strict';

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
        let submitting = false;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            if (submitting || form.dataset.submitting === '1') {
                return;
            }

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            submitting = true;
            form.dataset.submitting = '1';
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Sending...';
            btn.classList.add('is-sending');

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
                .then(function (res) {
                    return res.text().then(function (text) {
                        let data = {};
                        if (text) {
                            try {
                                data = JSON.parse(text);
                            } catch (parseErr) {
                                data = {};
                            }
                        }
                        return { ok: res.ok, status: res.status, data: data };
                    });
                })
                .then(function (result) {
                    if (result.ok && result.data && result.data.success) {
                        btn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Message Sent';
                        btn.classList.remove('is-sending');
                        btn.classList.add('is-sent');

                        if (typeof Swal !== 'undefined') {
                            return Swal.fire({
                                icon: 'success',
                                title: result.data.title || 'Message sent successfully!',
                                text: result.data.message || '',
                                confirmButtonColor: '#10b981',
                                confirmButtonText: 'Continue',
                                customClass: { popup: 'swal-premium' },
                            }).then(function () {
                                window.location.href = result.data.redirect || form.action;
                            });
                        }

                        window.location.href = result.data.redirect || form.action;
                        return;
                    }

                    const msg = (result.data && result.data.message)
                        ? result.data.message
                        : 'We couldn\'t send your message right now. Please try again.';
                    throw new Error(msg);
                })
                .catch(function (err) {
                    submitting = false;
                    form.dataset.submitting = '0';
                    btn.disabled = false;
                    btn.removeAttribute('aria-busy');
                    btn.innerHTML = defaultHtml;
                    btn.classList.remove('is-sending', 'is-sent');

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Could not send message',
                            text: err.message || 'We couldn\'t send your message right now. Please try again.',
                            confirmButtonColor: '#10b981',
                            customClass: { popup: 'swal-premium' },
                        });
                    } else {
                        alert(err.message || 'We couldn\'t send your message right now. Please try again.');
                    }
                });
        });
    }

    document.addEventListener('DOMContentLoaded', initContactForm);
})();
