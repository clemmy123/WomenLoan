import './reports-filters.js';

document.addEventListener('DOMContentLoaded', async () => {
    const payloadEl = document.getElementById('application-reports-chart-data');
    if (!payloadEl) {
        return;
    }

    const payload = JSON.parse(payloadEl.textContent);
    const Chart = window.Chart;
    if (!Chart) {
        return;
    }
    const isDark = document.documentElement.classList.contains('dark');

    Chart.defaults.color = isDark ? '#94a3b8' : '#64748b';
    Chart.defaults.borderColor = isDark ? 'rgba(148,163,184,0.1)' : 'rgba(148,163,184,0.2)';

    const barOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
        },
        scales: {
            y: { beginAtZero: true },
            x: { grid: { display: false } },
        },
    };

    const doughnutOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } },
    };

    const statusEl = document.getElementById('applicationStatusChart');
    if (statusEl) {
        new Chart(statusEl, {
            type: 'doughnut',
            data: {
                labels: payload.status_distribution.labels,
                datasets: [{
                    data: payload.status_distribution.data,
                    backgroundColor: ['#f59e0b', '#10b981', '#ef4444'],
                    borderWidth: 0,
                }],
            },
            options: doughnutOpts,
        });
    }

    const comparisonEl = document.getElementById('applicationComparisonChart');
    if (comparisonEl) {
        new Chart(comparisonEl, {
            type: 'bar',
            data: {
                labels: payload.applied_vs_received.labels,
                datasets: [{
                    data: payload.applied_vs_received.data,
                    backgroundColor: ['#0ea5e9', '#10b981'],
                    borderRadius: 6,
                }],
            },
            options: barOpts,
        });
    }
});
