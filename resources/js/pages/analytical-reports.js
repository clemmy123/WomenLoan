import './reports-filters.js';

document.addEventListener('DOMContentLoaded', async () => {
    const payloadEl = document.getElementById('analytical-chart-data');
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
            id: 'analyticalTypeChart',
            type: 'bar',
            data: {
                labels: payload.by_type_amounts.labels,
                datasets: [
                    {
                        label: payload.financial_trend.legend_disbursed ?? 'Disbursed',
                        data: payload.by_type_amounts.disbursed,
                        backgroundColor: '#0ea5e9',
                        borderRadius: 8,
                    },
                    {
                        label: payload.financial_trend.legend_paid ?? 'Repaid',
                        data: payload.by_type_amounts.paid,
                        backgroundColor: '#10b981',
                        borderRadius: 8,
                    },
                ],
            },
            options: barOpts,
        },
        {
            id: 'analyticalRepaymentChart',
            type: 'doughnut',
            data: {
                labels: payload.repayment_progress.labels,
                datasets: [{
                    data: payload.repayment_progress.data,
                    backgroundColor: ['#10b981', '#f59e0b'],
                    borderWidth: 0,
                }],
            },
            options: doughnutOpts,
        },
        {
            id: 'analyticalTrendChart',
            type: 'bar',
            data: {
                labels: payload.financial_trend.labels,
                datasets: [
                    {
                        label: payload.financial_trend.legend_disbursed ?? 'Disbursed',
                        data: payload.financial_trend.disbursed,
                        backgroundColor: '#0ea5e9',
                        borderRadius: 6,
                    },
                    {
                        label: payload.financial_trend.legend_paid ?? 'Repaid',
                        data: payload.financial_trend.paid,
                        backgroundColor: '#06b6d4',
                        borderRadius: 6,
                    },
                ],
            },
            options: barOpts,
        },
        {
            id: 'analyticalRegionChart',
            type: 'bar',
            data: {
                labels: payload.outstanding_by_region.labels,
                datasets: [{
                    data: payload.outstanding_by_region.data,
                    backgroundColor: '#0284c7',
                    borderRadius: 6,
                }],
            },
            options: {
                ...barOpts,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
            },
        },
    ];

    for (const cfg of charts) {
        const el = document.getElementById(cfg.id);
        if (el) {
            new Chart(el, cfg);
        }
    }
});
