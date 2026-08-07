// SmartWaste — Premium UI Engine
(function () {
    'use strict';

    const THEME_KEY = 'smartwaste-theme';

    /* ── Theme ─────────────────────────────────────────────────────────── */
    function getTheme() {
        return localStorage.getItem(THEME_KEY) || 'light';
    }

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.style.colorScheme = theme;
        localStorage.setItem(THEME_KEY, theme);
        updateThemeIcons(theme);
        window.dispatchEvent(new CustomEvent('themechange', { detail: theme }));
    }

    function updateThemeIcons(theme) {
        document.querySelectorAll('#themeToggle, #themeToggleAuth, #themeToggleLanding').forEach(btn => {
            if (!btn) return;
            const moon = btn.querySelector('.fa-moon, .theme-icon-dark');
            const sun = btn.querySelector('.fa-sun, .theme-icon-light');
            if (moon) moon.classList.toggle('d-none', theme === 'dark');
            if (sun) sun.classList.toggle('d-none', theme !== 'dark');
            if (!sun && moon) moon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        });
    }

    function initTheme() {
        setTheme(getTheme());
        document.querySelectorAll('#themeToggle, #themeToggleAuth, #themeToggleLanding').forEach(btn => {
            btn?.addEventListener('click', () => setTheme(getTheme() === 'light' ? 'dark' : 'light'));
        });
    }

    /* ── Page loader ───────────────────────────────────────────────────── */
    function initPageLoader() {
        const loader = document.getElementById('pageLoader');
        if (!loader) return;
        window.addEventListener('load', () => {
            setTimeout(() => loader.classList.add('is-hidden'), 300);
        });
        setTimeout(() => loader.classList.add('is-hidden'), 2500);
    }

    /* ── Sidebar ───────────────────────────────────────────────────────── */
    function initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');
        if (!sidebar || !toggle) return;

        const mq = window.matchMedia('(max-width: 991.98px)');

        const isMobile = () => mq.matches;

        const lockScroll = () => {
            /* Do not lock page scroll — keeps mobile nav usable without freezing the screen */
        };

        const close = () => {
            sidebar.classList.remove('is-open', 'open');
            overlay?.classList.remove('show', 'is-visible');
            lockScroll();
        };

        const open = () => {
            if (!isMobile()) return;
            sidebar.classList.add('is-open', 'open');
            if (overlay) {
                overlay.classList.add('show', 'is-visible');
            }
            lockScroll();
        };

        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (!isMobile()) return;
            if (sidebar.classList.contains('is-open')) close();
            else open();
        });

        overlay?.addEventListener('click', close);

        sidebar.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => { if (isMobile()) close(); });
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('is-open')) close();
        });

        mq.addEventListener('change', () => {
            if (!isMobile()) close();
        });

        window.addEventListener('resize', () => {
            if (!isMobile()) close();
        });
    }

    /* ── Landing navbar collapse ─────────────────────────────────────────── */
    function initLandingNav() {
        const collapseEl = document.getElementById('landingNav');
        if (!collapseEl || typeof bootstrap === 'undefined') return;

        const nav = collapseEl.closest('.navbar');
        const toggler = nav?.querySelector('.navbar-toggler');
        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });

        const closeNav = () => {
            if (collapseEl.classList.contains('show')) bsCollapse.hide();
        };

        collapseEl.addEventListener('shown.bs.collapse', () => {});

        collapseEl.addEventListener('hidden.bs.collapse', () => {});

        collapseEl.querySelectorAll('.nav-link, .btn-saas, .dropdown-item').forEach(link => {
            link.addEventListener('click', () => {
                if (link.classList.contains('dropdown-toggle')) return;
                if (window.innerWidth < 992) closeNav();
            });
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeNav();
        });

        toggler?.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }

    /* ── Animated counters ───────────────────────────────────────────────── */
    function initCounters() {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                if (el.dataset.animated) return;
                el.dataset.animated = '1';
                const target = +el.dataset.target;
                if (!target) return;
                let current = 0;
                const step = Math.max(1, Math.ceil(target / 60));
                const timer = setInterval(() => {
                    current += step;
                    if (current >= target) { current = target; clearInterval(timer); }
                    el.textContent = current.toLocaleString() + (target >= 1000 ? '+' : '');
                }, 25);
            });
        }, { threshold: 0.3 });
        document.querySelectorAll('.counter').forEach(el => observer.observe(el));
    }

    /* ── Table: search, sort, pagination ───────────────────────────────── */
    function initDataTables() {
        document.querySelectorAll('.saas-table-wrapper').forEach(wrapper => {
            const card = wrapper.closest('.saas-card') || wrapper.closest('.glass-card');
            const table = wrapper.querySelector('.saas-table');
            if (!table) return;

            const tbody = table.querySelector('tbody');
            const allRows = Array.from(tbody?.querySelectorAll('tr') || []);
            if (!allRows.length) return;

            const perPage = parseInt(table.dataset.perPage || '10', 10);
            let currentPage = 1;
            let filteredRows = [...allRows];
            let sortCol = -1;
            let sortDir = 'asc';

            const searchInput = card?.querySelector('.table-search-input');
            const paginationEl = card?.querySelector('.table-pagination');
            const infoEl = card?.querySelector('.pagination-info');
            const prevBtn = card?.querySelector('.page-prev');
            const nextBtn = card?.querySelector('.page-next');
            const pagesEl = card?.querySelector('.pagination-pages');

            function render() {
                const total = filteredRows.length;
                const totalPages = Math.max(1, Math.ceil(total / perPage));
                if (currentPage > totalPages) currentPage = totalPages;

                allRows.forEach(r => r.style.display = 'none');
                const start = (currentPage - 1) * perPage;
                filteredRows.slice(start, start + perPage).forEach(r => r.style.display = '');

                if (infoEl) {
                    infoEl.textContent = total
                        ? `Showing ${start + 1}–${Math.min(start + perPage, total)} of ${total}`
                        : 'No results';
                }
                if (prevBtn) prevBtn.disabled = currentPage <= 1;
                if (nextBtn) nextBtn.disabled = currentPage >= totalPages;

                if (pagesEl) {
                    pagesEl.innerHTML = '';
                    for (let i = 1; i <= Math.min(totalPages, 5); i++) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'page-btn' + (i === currentPage ? ' active' : '');
                        btn.textContent = i;
                        btn.addEventListener('click', () => { currentPage = i; render(); });
                        pagesEl.appendChild(btn);
                    }
                }
            }

            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    const q = searchInput.value.toLowerCase();
                    filteredRows = allRows.filter(r => r.textContent.toLowerCase().includes(q));
                    currentPage = 1;
                    render();
                });
            }

            table.querySelectorAll('th.sortable').forEach((th, idx) => {
                th.addEventListener('click', () => {
                    if (sortCol === idx) sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                    else { sortCol = idx; sortDir = 'asc'; }
                    table.querySelectorAll('th.sortable').forEach(h => h.classList.remove('asc', 'desc'));
                    th.classList.add(sortDir);

                    filteredRows.sort((a, b) => {
                        const av = (a.cells[idx]?.textContent || '').trim().toLowerCase();
                        const bv = (b.cells[idx]?.textContent || '').trim().toLowerCase();
                        const cmp = av.localeCompare(bv, undefined, { numeric: true });
                        return sortDir === 'asc' ? cmp : -cmp;
                    });
                    render();
                });
            });

            if (prevBtn) prevBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; render(); } });
            if (nextBtn) nextBtn.addEventListener('click', () => {
                const totalPages = Math.ceil(filteredRows.length / perPage);
                if (currentPage < totalPages) { currentPage++; render(); }
            });

            if (paginationEl) render();
        });
    }

    function initTableSearch() {
        document.querySelectorAll('.saas-table-wrapper').forEach(wrapper => {
            if (wrapper.closest('.saas-card')?.querySelector('.table-pagination')) return;
            const card = wrapper.closest('.saas-card') || wrapper.closest('.glass-card');
            const input = card?.querySelector('.table-search-input');
            const table = wrapper.querySelector('.saas-table');
            if (!input || !table) return;
            input.addEventListener('input', () => {
                const q = input.value.toLowerCase();
                table.querySelectorAll('tbody tr').forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            });
        });
    }

    /* ── Global search ───────────────────────────────────────────────────── */
    function initGlobalSearch() {
        const input = document.getElementById('globalSearch');
        if (!input) return;
        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                const tableInput = document.querySelector('.table-search-input');
                if (tableInput) { tableInput.value = input.value; tableInput.dispatchEvent(new Event('input')); }
            }
        });
    }

    /* ── Button ripple ─────────────────────────────────────────────────── */
    function initRipple() {
        document.addEventListener('click', e => {
            const btn = e.target.closest('.btn-saas');
            if (!btn) return;
            const rect = btn.getBoundingClientRect();
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
            btn.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    }

    /* ── FAQ accordion ───────────────────────────────────────────────────── */
    function initFaqSearch() {
        const input = document.getElementById('faqSearch');
        const list = document.getElementById('faqList');
        const meta = document.getElementById('faqSearchMeta');
        const empty = document.getElementById('faqNoResults');
        if (!input || !list) return;

        const items = list.querySelectorAll('.faq-item');
        const filter = () => {
            const q = input.value.trim().toLowerCase();
            let visible = 0;
            items.forEach(item => {
                const text = item.dataset.faqText || '';
                const show = !q || text.includes(q);
                item.classList.toggle('is-hidden', !show);
                if (show) visible++;
            });
            if (meta) meta.textContent = q ? `${visible} of ${items.length} questions` : `${items.length} questions`;
            if (empty) empty.classList.toggle('d-none', visible > 0);
        };
        input.addEventListener('input', filter);
    }

    function initFaq() {
        document.querySelectorAll('.faq-question').forEach(btn => {
            btn.addEventListener('click', () => {
                const item = btn.closest('.faq-item');
                const wasOpen = item.classList.contains('is-open');
                item.closest('.faq-section')?.querySelectorAll('.faq-item').forEach(i => i.classList.remove('is-open'));
                if (!wasOpen) item.classList.add('is-open');
            });
        });
    }

    /* ── Scroll reveal ───────────────────────────────────────────────────── */
    function initReveal() {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
    }

    /* ── Form validation ─────────────────────────────────────────────────── */
    function initFormValidation() {
        document.querySelectorAll('form[data-validate]').forEach(form => {
            form.addEventListener('submit', e => {
                let valid = true;
                form.querySelectorAll('[required]').forEach(field => {
                    const wrap = field.closest('.form-floating-modern') || field.parentElement;
                    if (!field.value.trim()) {
                        valid = false;
                        wrap?.classList.add('is-invalid');
                    } else {
                        wrap?.classList.remove('is-invalid');
                    }
                });
                const pw = form.querySelector('[name=password]');
                const pwc = form.querySelector('[name=password_confirm]');
                if (pw && pwc && pw.value !== pwc.value) {
                    valid = false;
                    pwc.closest('.form-floating-modern')?.classList.add('is-invalid');
                }
                if (!valid) e.preventDefault();
            });
            form.querySelectorAll('input, select, textarea').forEach(field => {
                field.addEventListener('input', () => {
                    field.closest('.form-floating-modern')?.classList.remove('is-invalid');
                });
            });
        });
    }

    /* ── Skeleton hide on load ───────────────────────────────────────────── */
    function initSkeletons() {
        document.querySelectorAll('[data-skeleton]').forEach(el => {
            el.classList.add('skeleton-loading');
            window.addEventListener('load', () => {
                setTimeout(() => el.classList.remove('skeleton-loading'), 400);
            });
        });
    }

    /* ── Progress bars animate ───────────────────────────────────────────── */
    function initProgressBars() {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                entry.target.querySelectorAll('[data-progress]').forEach(bar => {
                    bar.style.width = bar.dataset.progress + '%';
                });
            });
        }, { threshold: 0.5 });
        document.querySelectorAll('.progress-premium').forEach(el => observer.observe(el));
    }

    /* ── Calendar widget ─────────────────────────────────────────────────── */
    function initCalendar() {
        document.querySelectorAll('[data-calendar]').forEach(el => {
            const now = new Date();
            const month = now.getMonth();
            const year = now.getFullYear();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const firstDay = new Date(year, month, 1).getDay();
            const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            const title = el.querySelector('.calendar-title');
            if (title) title.textContent = monthNames[month] + ' ' + year;
            const grid = el.querySelector('.calendar-grid');
            if (!grid) return;
            for (let i = 0; i < firstDay; i++) {
                const d = document.createElement('div');
                d.className = 'calendar-day other-month';
                grid.appendChild(d);
            }
            for (let d = 1; d <= daysInMonth; d++) {
                const day = document.createElement('div');
                day.className = 'calendar-day' + (d === now.getDate() ? ' today' : '');
                day.textContent = d;
                grid.appendChild(day);
            }
        });
    }

    /* ── SweetAlert helpers ──────────────────────────────────────────────── */
    window.confirmLogout = function (e) {
        e.preventDefault();
        const href = e.currentTarget.href;
        Swal.fire({
            title: 'Sign out?',
            text: 'You will be redirected to the home page.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, logout',
            customClass: { popup: 'swal-premium' }
        }).then(r => { if (r.isConfirmed) window.location.href = href; });
        return false;
    };

    window.confirmDelete = function (message, callback) {
        Swal.fire({
            title: 'Are you sure?',
            text: message || 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Delete'
        }).then(r => { if (r.isConfirmed && callback) callback(); });
    };

    window.exportTableCsv = function (tableId, filename) {
        const table = document.getElementById(tableId) || document.querySelector('.saas-table');
        if (!table) return;
        const rows = Array.from(table.querySelectorAll('tr')).filter(r => r.style.display !== 'none');
        const csv = rows.map(row =>
            Array.from(row.querySelectorAll('th, td')).map(cell =>
                '"' + cell.textContent.trim().replace(/"/g, '""') + '"'
            ).join(',')
        ).join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = (filename || 'export') + '.csv';
        a.click();
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Export downloaded', showConfirmButton: false, timer: 2500 });
    };

    function syncOfflineData() {
        const queue = JSON.parse(localStorage.getItem('collector_queue') || '[]');
        if (!queue.length || !window.BASE_URL) return;
        fetch(window.BASE_URL + 'collector/sync', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ actions: queue })
        }).then(() => {
            localStorage.removeItem('collector_queue');
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Offline data synced', showConfirmButton: false, timer: 2500 });
        });
    }

    window.saveOffline = function (action) {
        const queue = JSON.parse(localStorage.getItem('collector_queue') || '[]');
        queue.push(action);
        localStorage.setItem('collector_queue', JSON.stringify(queue));
        Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Saved offline', showConfirmButton: false, timer: 2000 });
    };

    /* ── Fleet background video ──────────────────────────────────────────── */
    function initFleetVideo() {
        const video = document.querySelector('.fleet-bg-video');
        if (!video) return;

        const rate = parseFloat(video.dataset.playbackRate || '0.65');
        video.playbackRate = Math.min(1, Math.max(0.5, rate));

        const play = () => {
            video.play().catch(() => {});
        };

        if (video.readyState >= 2) {
            play();
        } else {
            video.addEventListener('loadeddata', play, { once: true });
        }

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                video.pause();
            } else {
                play();
            }
        });
    }

    /* ── Boot ────────────────────────────────────────────────────────────── */
    /* ── Broken image fallback (ngrok / missing files) ─────────────────── */
    function initImageFallback() {
        const fallback = (window.ASSET_URL || '') + 'images/placeholders/no-image.jpg';
        if (!fallback || fallback === 'images/placeholders/no-image.jpg') return;

        document.addEventListener('error', (event) => {
            const el = event.target;
            if (!(el instanceof HTMLImageElement) || el.dataset.fallbackApplied === '1') return;
            if (!el.src || el.src.includes('/placeholders/no-image.jpg')) return;
            el.dataset.fallbackApplied = '1';
            el.src = fallback;
        }, true);
    }

    /* ── Password fields (SmartWastePassword module) ──────────────────── */
    function initPasswordFields() {
        if (typeof window.SmartWastePassword !== 'undefined') {
            window.SmartWastePassword.init();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        initTheme();
        initPageLoader();
        initImageFallback();
        initSidebar();
        initLandingNav();
        initFleetVideo();
        initCounters();
        initDataTables();
        initTableSearch();
        initGlobalSearch();
        initRipple();
        initFaq();
        initFaqSearch();
        initReveal();
        initFormValidation();
        initPasswordFields();
        initSkeletons();
        initProgressBars();
        initCalendar();
        if (typeof AOS !== 'undefined') AOS.init({ duration: 700, once: true, offset: 40, easing: 'ease-out-cubic' });
        if (navigator.onLine === false) window.addEventListener('online', syncOfflineData);
    });
})();
