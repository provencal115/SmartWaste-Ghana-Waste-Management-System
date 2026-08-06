// Chart.js — theme-aware premium charts with live theme switching
const chartRegistry = [];

function chartColors() {
    const dark = document.documentElement.getAttribute('data-theme') === 'dark';
    return {
        primary: dark ? '#34d399' : '#10b981',
        primaryBg: dark ? 'rgba(52,211,153,0.15)' : 'rgba(16,185,129,0.12)',
        accent: dark ? '#818cf8' : '#6366f1',
        grid: dark ? 'rgba(148,163,184,0.12)' : 'rgba(148,163,184,0.2)',
        text: dark ? '#cbd5e1' : '#64748b',
        tooltipBg: dark ? '#1e293b' : '#ffffff',
        tooltipText: dark ? '#f1f5f9' : '#0f172a',
        palette: dark
            ? ['#34d399', '#818cf8', '#fbbf24', '#f87171', '#38bdf8', '#a78bfa']
            : ['#10b981', '#6366f1', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6']
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
                            return ' GH₵ ' + Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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

    const formatCurrency = (v) => 'GH₵ ' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 });

    const render = (period) => {
        const set = datasets[period] || datasets['30'] || { labels: [], data: [] };
        initLineChart(canvasId, set.labels, set.data, 'Revenue (GH₵)');
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
    });
}

window.addEventListener('themechange', () => {
    refreshAllCharts();
});
