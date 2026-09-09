import './reports-filters.js';

document.addEventListener('DOMContentLoaded', async () => {
    const payloadEl = document.getElementById('general-reports-chart-data');
    if (!payloadEl) {
        return;
    }

    const payload = JSON.parse(payloadEl.textContent);
    const { default: Chart } = await import('chart.js/auto');
    const isDark = document.documentElement.classList.contains('dark');

    Chart.defaults.color = isDark ? '#94a3b8' : '#64748b';
    Chart.defaults.borderColor = isDark ? 'rgba(148,163,184,0.1)' : 'rgba(148,163,184,0.2)';

    const pieEl = document.getElementById('generalReportPieChart');
    if (pieEl && payload.outcome_pie) {
        new Chart(pieEl, {
            type: 'doughnut',
            data: {
                labels: payload.outcome_pie.labels,
                datasets: [{
                    data: payload.outcome_pie.data,
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } },
                },
            },
        });
    }

    const barEl = document.getElementById('generalReportBarChart');
    if (barEl && payload.outcome_bars) {
        new Chart(barEl, {
            type: 'bar',
            data: {
                labels: payload.outcome_bars.labels,
                datasets: [{
                    data: payload.outcome_bars.data,
                    backgroundColor: ['#0ea5e9', '#10b981', '#f59e0b', '#ef4444'],
                    borderRadius: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { grid: { display: false } },
                },
            },
        });
    }
});
