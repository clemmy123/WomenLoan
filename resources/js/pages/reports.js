document.addEventListener('DOMContentLoaded', async () => {
    const payloadEl = document.getElementById('reports-chart-data');
    if (!payloadEl) {
        return;
    }

    const payload = JSON.parse(payloadEl.textContent);
    const { default: Chart } = await import('chart.js/auto');
    const isDark = document.documentElement.classList.contains('dark');

    Chart.defaults.color = isDark ? '#94a3b8' : '#64748b';
    Chart.defaults.borderColor = isDark ? 'rgba(148,163,184,0.1)' : 'rgba(148,163,184,0.2)';

    const barOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false } },
        },
    };

    const doughnutOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'right', labels: { boxWidth: 12, padding: 12 } } },
    };

    const charts = [
        { id: 'appsChart', type: 'bar', data: { labels: payload.monthly.labels, datasets: [{ data: payload.monthly.data, backgroundColor: '#6366f1', borderRadius: 6 }] }, options: barOpts },
        { id: 'disbChart', type: 'line', data: { labels: payload.disbursements.labels, datasets: [{ data: payload.disbursements.data, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.4 }] }, options: barOpts },
        { id: 'statusChart', type: 'doughnut', data: { labels: payload.status.labels, datasets: [{ data: payload.status.data, backgroundColor: ['#6366f1', '#f59e0b', '#10b981', '#8b5cf6', '#ef4444', '#06b6d4', '#f472b6'], borderWidth: 0 }] }, options: doughnutOpts },
        { id: 'regionChart', type: 'bar', data: { labels: payload.region.labels, datasets: [{ data: payload.region.data, backgroundColor: '#8b5cf6', borderRadius: 6 }] }, options: { ...barOpts, indexAxis: 'y' } },
        { id: 'pipelineChart', type: 'bar', data: { labels: payload.pipeline.labels, datasets: [{ data: payload.pipeline.data, backgroundColor: ['#6366f1', '#7c3aed', '#a78bfa', '#c4b5fd', '#818cf8', '#60a5fa', '#34d399', '#fbbf24', '#f472b6'], borderRadius: 6 }] }, options: barOpts },
    ];

    for (const cfg of charts) {
        const el = document.getElementById(cfg.id);
        if (el) {
            new Chart(el, cfg);
        }
    }
});
