import './reports-filters.js';

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
        plugins: {
            legend: { position: 'top', labels: { boxWidth: 12, padding: 16 } },
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

    const charts = [
        {
            id: 'financialTrendChart',
            type: 'bar',
            data: {
                labels: payload.financial_trend.labels,
                datasets: [
                    {
                        label: payload.financial_trend.legend_disbursed ?? 'Disbursed',
                        data: payload.financial_trend.disbursed,
                        backgroundColor: '#10b981',
                        borderRadius: 6,
                    },
                    {
                        label: payload.financial_trend.legend_paid ?? 'Paid',
                        data: payload.financial_trend.paid,
                        backgroundColor: '#6366f1',
                        borderRadius: 6,
                    },
                ],
            },
            options: barOpts,
        },
        {
            id: 'regionChart',
            type: 'bar',
            data: {
                labels: payload.by_region.labels,
                datasets: [{ data: payload.by_region.data, backgroundColor: '#8b5cf6', borderRadius: 6 }],
            },
            options: { ...barOpts, indexAxis: 'y', plugins: { legend: { display: false } } },
        },
        {
            id: 'loanTypeChart',
            type: 'doughnut',
            data: {
                labels: payload.loan_type.labels,
                datasets: [{ data: payload.loan_type.data, backgroundColor: ['#6366f1', '#f59e0b'], borderWidth: 0 }],
            },
            options: doughnutOpts,
        },
        {
            id: 'disabilityChart',
            type: 'doughnut',
            data: {
                labels: payload.disability.labels,
                datasets: [{ data: payload.disability.data, backgroundColor: ['#06b6d4', '#cbd5e1'], borderWidth: 0 }],
            },
            options: doughnutOpts,
        },
        {
            id: 'maritalStatusChart',
            type: 'doughnut',
            data: {
                labels: payload.marital_status.labels,
                datasets: [{
                    data: payload.marital_status.data,
                    backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#f472b6'],
                    borderWidth: 0,
                }],
            },
            options: doughnutOpts,
        },
        {
            id: 'ageChart',
            type: 'bar',
            data: {
                labels: payload.age_buckets.labels,
                datasets: [{ data: payload.age_buckets.data, backgroundColor: '#0ea5e9', borderRadius: 6 }],
            },
            options: { ...barOpts, plugins: { legend: { display: false } } },
        },
    ];

    for (const cfg of charts) {
        const el = document.getElementById(cfg.id);
        if (el) {
            new Chart(el, cfg);
        }
    }
});
