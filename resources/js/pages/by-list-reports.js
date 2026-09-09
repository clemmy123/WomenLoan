import './reports-filters.js';

document.addEventListener('DOMContentLoaded', async () => {
    const payloadEl = document.getElementById('by-list-reports-chart-data');
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

    const breakdownBarOpts = {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: { position: 'top', labels: { boxWidth: 12, padding: 16 } },
        },
        scales: {
            x: { beginAtZero: true },
            y: { grid: { display: false } },
        },
    };

    function renderBreakdownChart(canvasId, data, options = {}) {
        const el = document.getElementById(canvasId);
        if (!el || !data?.labels?.length) {
            return;
        }

        const vertical = options.vertical ?? data.vertical ?? false;

        new Chart(el, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: data.legend_disbursed ?? 'Disbursed',
                        data: data.disbursed,
                        backgroundColor: '#10b981',
                        borderRadius: 4,
                    },
                    {
                        label: data.legend_outstanding ?? 'Outstanding',
                        data: data.outstanding,
                        backgroundColor: '#f59e0b',
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                ...breakdownBarOpts,
                indexAxis: vertical ? 'x' : 'y',
                scales: vertical
                    ? {
                        y: { beginAtZero: true },
                        x: { grid: { display: false } },
                    }
                    : breakdownBarOpts.scales,
            },
        });
    }

    renderBreakdownChart('byRegionAllRegionsChart', payload.by_region);
    renderBreakdownChart('bySectorAllSectorsChart', payload.by_sector);
    renderBreakdownChart('byTypeAllTypesChart', payload.by_type);
    renderBreakdownChart('byBankAllBanksChart', payload.by_bank);
    renderBreakdownChart('byMonthlyJanDecChart', payload.by_monthly);
    renderBreakdownChart('byAgeBucketsChart', payload.by_age);

    const financialEl = document.getElementById('byReportFinancialChart');
    if (financialEl) {
        new Chart(financialEl, {
            type: 'bar',
            data: {
                labels: payload.financial.labels,
                datasets: [{
                    data: payload.financial.data,
                    backgroundColor: ['#10b981', '#0284c7', '#f59e0b'],
                    borderRadius: 6,
                }],
            },
            options: barOpts,
        });
    }

    const typeEl = document.getElementById('byReportTypeChart');
    if (typeEl && payload.loan_type.data.some((v) => v > 0)) {
        new Chart(typeEl, {
            type: 'doughnut',
            data: {
                labels: payload.loan_type.labels,
                datasets: [{
                    data: payload.loan_type.data,
                    backgroundColor: ['#0ea5e9', '#f59e0b'],
                    borderWidth: 0,
                }],
            },
            options: doughnutOpts,
        });
    }

    const topEl = document.getElementById('byReportTopDisbursedChart');
    if (topEl && payload.top_disbursed.labels.length > 0) {
        new Chart(topEl, {
            type: 'bar',
            data: {
                labels: payload.top_disbursed.labels,
                datasets: [{
                    data: payload.top_disbursed.data,
                    backgroundColor: '#0ea5e9',
                    borderRadius: 6,
                }],
            },
            options: { ...barOpts, indexAxis: 'y' },
        });
    }
});
