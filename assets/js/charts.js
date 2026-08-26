// Chart.js — theme-aware premium charts with live theme switching
const chartRegistry = [];

function chartHasData(labels, data) {
    const labelOk = Array.isArray(labels) && labels.length > 0;
    const values = Array.isArray(data) ? data : [];
    const dataOk = values.some(v => Number(v) > 0);
    return labelOk && (dataOk || values.length > 0);
}

function showChartEmptyState(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const wrap = canvas.closest('.chart-wrap, .revenue-chart-wrap, .saas-card-body, .glass-card') || canvas.parentElement;
    if (!wrap || wrap.querySelector('.chart-empty-state')) return;
    canvas.style.display = 'none';
    const note = document.createElement('p');
    note.className = 'chart-empty-state text-secondary small text-center py-4 mb-0';
    note.textContent = 'No data available yet.';
    wrap.appendChild(note);
}

function chartColors() {
    const root = document.documentElement;
    const styles = getComputedStyle(root);
    const pick = (name, fallback) => {
        const v = styles.getPropertyValue(name).trim();
        return v || fallback;
    };
    const dark = root.getAttribute('data-theme') === 'dark';
    const primary = pick('--color-primary', dark ? '#34d399' : '#059669');
    return {
        primary,
        primaryBg: pick('--color-primary-light', dark ? 'rgba(52,211,153,0.15)' : 'rgba(5,150,105,0.12)'),
        accent: pick('--color-accent', dark ? '#60a5fa' : '#2563eb'),
        grid: dark ? 'rgba(148,163,184,0.12)' : 'rgba(148,163,184,0.2)',
        text: pick('--text-muted', dark ? '#94a3b8' : '#64748b'),
        tooltipBg: pick('--bg-surface-raised', dark ? '#1e293b' : '#ffffff'),
        tooltipText: pick('--text-primary', dark ? '#f1f5f9' : '#0f172a'),
        palette: dark
            ? [primary, '#818cf8', '#fbbf24', '#f87171', '#38bdf8', '#a78bfa']
            : [primary, '#6366f1', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6']
    };
}

function baseChartOptions() {
    const c = chartColors();
    return {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { labels: { color: c.text, font: { family: 'Inter', size: 11 } } },
            tooltip: {
                backgroundColor: c.tooltipBg,
                titleColor: c.tooltipText,
                bodyColor: c.tooltipText,
                borderColor: c.grid,
                borderWidth: 1
            }
        },
        scales: {
            x: { grid: { color: c.grid }, ticks: { color: c.text, font: { size: 10 } } },
            y: { grid: { color: c.grid }, ticks: { color: c.text, font: { size: 10 } }, beginAtZero: true }
        }
    };
}

function destroyChart(canvasId) {
    const el = document.getElementById(canvasId);
    if (!el || typeof Chart === 'undefined') return;
    const existing = Chart.getChart(el);
    if (existing) existing.destroy();
}

function renderLineChart(entry) {
    destroyChart(entry.canvasId);
    const ctx = document.getElementById(entry.canvasId);
    if (!ctx || typeof Chart === 'undefined') return;
    if (!chartHasData(entry.labels, entry.data)) {
        showChartEmptyState(entry.canvasId);
        return;
    }
    ctx.style.display = '';
    const c = chartColors();
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: entry.labels,
            datasets: [{
                label: entry.label,
                data: entry.data,
                borderColor: c.primary,
                backgroundColor: c.primaryBg,
                fill: true,
                tension: 0.45,
                borderWidth: 2.5,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: c.primary
            }]
        },
        options: {
            ...baseChartOptions(),
            animation: { duration: entry.animate === false ? 0 : 1200, easing: 'easeOutQuart' },
            plugins: {
                ...baseChartOptions().plugins,
                legend: { display: false },
                tooltip: {
                    ...baseChartOptions().plugins.tooltip,
                    callbacks: {
                        label(ctx) {
                            const val = ctx.parsed.y ?? 0;
                            return ' GH\u20B5 ' + Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }
                }
            },
            interaction: { intersect: false, mode: 'index' }
        }
    });
}

function renderBarChart(entry) {
    destroyChart(entry.canvasId);
    const ctx = document.getElementById(entry.canvasId);
    if (!ctx || typeof Chart === 'undefined') return;
    if (!chartHasData(entry.labels, entry.data)) {
        showChartEmptyState(entry.canvasId);
        return;
    }
    ctx.style.display = '';
    const c = chartColors();
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: entry.labels,
            datasets: [{ data: entry.data, backgroundColor: c.palette, borderRadius: 10, borderSkipped: false }]
        },
        options: {
            ...baseChartOptions(),
            animation: { duration: entry.animate === false ? 0 : 1000, easing: 'easeOutQuart' },
            plugins: { ...baseChartOptions().plugins, legend: { display: false } }
        }
    });
}

function renderPieChart(entry) {
    destroyChart(entry.canvasId);
    const ctx = document.getElementById(entry.canvasId);
    if (!ctx || typeof Chart === 'undefined') return;
    if (!chartHasData(entry.labels, entry.data)) {
        showChartEmptyState(entry.canvasId);
        return;
    }
    ctx.style.display = '';
    const c = chartColors();
    new Chart(ctx, {
        type: 'doughnut',
        data: { labels: entry.labels, datasets: [{ data: entry.data, backgroundColor: c.palette, borderWidth: 0, hoverOffset: 8 }] },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { color: c.text, padding: 16, font: { size: 11 } } },
                tooltip: baseChartOptions().plugins.tooltip
            }
        }
    });
}

function registerChart(entry) {
    const idx = chartRegistry.findIndex(c => c.canvasId === entry.canvasId);
    if (idx >= 0) chartRegistry[idx] = entry;
    else chartRegistry.push(entry);
}

function initLineChart(canvasId, labels, data, label) {
    const entry = { canvasId, type: 'line', labels, data, label, animate: true };
    registerChart(entry);
    renderLineChart(entry);
}

/** Revenue chart with 7d / 30d / 6mo period toggle. */
function initRevenueTrendChart(canvasId, datasets, defaultPeriod = '30') {
    const wrap = document.getElementById(canvasId)?.closest('.revenue-chart-wrap');
    if (!wrap || typeof Chart === 'undefined') return;

    const render = (period) => {
        const set = datasets[period] || datasets['30'] || { labels: [], data: [] };
        initLineChart(canvasId, set.labels, set.data, 'Revenue (GH\u20B5)');
    };

    wrap.querySelectorAll('[data-revenue-period]').forEach((btn) => {
        btn.addEventListener('click', () => {
            wrap.querySelectorAll('[data-revenue-period]').forEach((b) => b.classList.remove('active'));
            btn.classList.add('active');
            render(btn.dataset.revenuePeriod);
        });
    });

    render(defaultPeriod);
}

/** Stacked bar — completed vs missed vs delayed collections. */
function initCollectionTrendChart(canvasId, datasets, defaultPeriod = 'daily') {
    const wrap = document.getElementById(canvasId)?.closest('.collection-chart-wrap');
    if (!wrap || typeof Chart === 'undefined') return;

    const render = (period) => {
        destroyChart(canvasId);
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;
        const set = datasets[period] || datasets.daily || { labels: [], completed: [], missed: [], delayed: [] };
        const c = chartColors();

        const entry = {
            canvasId,
            type: 'stackedBar',
            labels: set.labels,
            datasets: [
                { label: 'Completed', data: set.completed, backgroundColor: c.primary },
                { label: 'Missed', data: set.missed, backgroundColor: c.palette[3] },
                { label: 'Delayed', data: set.delayed, backgroundColor: c.palette[2] }
            ],
            animate: true
        };
        registerChart(entry);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: set.labels,
                datasets: entry.datasets.map(d => ({ ...d, borderRadius: 6, borderSkipped: false }))
            },
            options: {
                ...baseChartOptions(),
                plugins: {
                    ...baseChartOptions().plugins,
                    legend: { position: 'bottom', labels: { color: c.text, font: { size: 11 } } }
                },
                scales: {
                    x: { stacked: true, grid: { color: c.grid }, ticks: { color: c.text, font: { size: 10 } } },
                    y: { stacked: true, grid: { color: c.grid }, ticks: { color: c.text, font: { size: 10 } }, beginAtZero: true }
                }
            }
        });
    };

    wrap.querySelectorAll('[data-collection-period]').forEach((btn) => {
        btn.addEventListener('click', () => {
            wrap.querySelectorAll('[data-collection-period]').forEach((b) => b.classList.remove('active'));
            btn.classList.add('active');
            render(btn.dataset.collectionPeriod);
        });
    });

    render(defaultPeriod);
}

/** Rating distribution 1–5 stars. */
function initRatingChart(canvasId, counts) {
    destroyChart(canvasId);
    const ctx = document.getElementById(canvasId);
    if (!ctx || typeof Chart === 'undefined') return;
    const c = chartColors();
    const labels = ['1★', '2★', '3★', '4★', '5★'];
    const data = Array.isArray(counts) ? counts : [0, 0, 0, 0, 0];

    registerChart({ canvasId, type: 'bar', labels, data, animate: true });

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{ data, backgroundColor: c.palette, borderRadius: 8, borderSkipped: false }]
        },
        options: {
            ...baseChartOptions(),
            plugins: { ...baseChartOptions().plugins, legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: c.text } },
                y: { grid: { color: c.grid }, ticks: { color: c.text, stepSize: 1 }, beginAtZero: true }
            }
        }
    });
}

function initBarChart(canvasId, labels, data) {
    const entry = { canvasId, type: 'bar', labels, data, animate: true };
    registerChart(entry);
    renderBarChart(entry);
}

function initPieChart(canvasId, labels, data) {
    const entry = { canvasId, type: 'pie', labels, data, animate: true };
    registerChart(entry);
    renderPieChart(entry);
}

function refreshAllCharts() {
    chartRegistry.forEach(entry => {
        entry.animate = false;
        if (entry.type === 'line') renderLineChart(entry);
        else if (entry.type === 'bar') renderBarChart(entry);
        else if (entry.type === 'pie') renderPieChart(entry);
        else if (entry.type === 'multiLine' && entry.datasets) renderMultiLineChart(entry);
        else if (entry.type === 'stackedBar' && entry.datasets) renderStackedBarChart(entry);
    });
}

function initMultiLineChart(canvasId, labels, datasets) {
    const entry = { canvasId, type: 'multiLine', labels, datasets, animate: true };
    registerChart(entry);
    renderMultiLineChart(entry);
}

function renderMultiLineChart(entry) {
    destroyChart(entry.canvasId);
    const ctx = document.getElementById(entry.canvasId);
    if (!ctx || typeof Chart === 'undefined') return;
    const c = chartColors();
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: entry.labels,
            datasets: (entry.datasets || []).map((d, i) => ({
                label: d.label,
                data: d.data,
                borderColor: c.palette[i % c.palette.length],
                backgroundColor: 'transparent',
                tension: 0.35,
                borderWidth: 2,
                pointRadius: 2,
                pointHoverRadius: 5
            }))
        },
        options: {
            ...baseChartOptions(),
            animation: { duration: entry.animate === false ? 0 : 1000 },
            plugins: {
                ...baseChartOptions().plugins,
                legend: { position: 'bottom', labels: { color: c.text, font: { size: 11 } } }
            }
        }
    });
}

function initStackedBarChart(canvasId, labels, datasets) {
    const entry = { canvasId, type: 'stackedBar', labels, datasets, animate: true };
    registerChart(entry);
    renderStackedBarChart(entry);
}

function renderStackedBarChart(entry) {
    destroyChart(entry.canvasId);
    const ctx = document.getElementById(entry.canvasId);
    if (!ctx || typeof Chart === 'undefined') return;
    const c = chartColors();
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: entry.labels,
            datasets: (entry.datasets || []).map((d, i) => ({
                ...d,
                backgroundColor: d.backgroundColor || c.palette[i % c.palette.length],
                borderRadius: 6,
                borderSkipped: false
            }))
        },
        options: {
            ...baseChartOptions(),
            animation: { duration: entry.animate === false ? 0 : 1000 },
            plugins: { ...baseChartOptions().plugins, legend: { position: 'bottom', labels: { color: c.text } } },
            scales: {
                x: { stacked: true, grid: { color: c.grid }, ticks: { color: c.text } },
                y: { stacked: true, grid: { color: c.grid }, ticks: { color: c.text }, beginAtZero: true }
            }
        }
    });
}

window.addEventListener('themechange', () => {
    refreshAllCharts();
});
