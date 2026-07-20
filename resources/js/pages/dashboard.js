function chartTheme() {
    const isDark = document.documentElement.classList.contains('dark');

    return {
        color: isDark ? '#94a3b8' : '#64748b',
        grid: isDark ? 'rgba(148,163,184,0.1)' : 'rgba(148,163,184,0.2)',
    };
}

function baseOptions(type) {
    const { color, grid } = chartTheme();

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: type === 'doughnut' ? { position: 'right', labels: { boxWidth: 12, padding: 12, color } } : { display: false },
        },
        scales: type !== 'doughnut' ? {
            y: { beginAtZero: true, ticks: { precision: 0, color }, grid: { color: grid } },
            x: { ticks: { color }, grid: { display: false } },
        } : undefined,
    };
}

document.addEventListener('DOMContentLoaded', async () => {
    const payloadEl = document.getElementById('dashboard-chart-data');
    if (!payloadEl) {
        return;
    }

    const payload = JSON.parse(payloadEl.textContent);
    const { default: Chart } = await import('chart.js/auto');
    const { color, grid } = chartTheme();

    Chart.defaults.color = color;
    Chart.defaults.borderColor = grid;

    const trend = document.getElementById('trendChart');
    if (trend) {
        new Chart(trend, {
            type: 'line',
            data: {
                labels: payload.monthly.labels,
                datasets: [{
                    label: payload.monthly.label,
                    data: payload.monthly.data,
                    borderColor: '#0ea5e9',
                    backgroundColor: 'rgba(14,165,233,0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#0ea5e9',
                }],
            },
            options: baseOptions('line'),
        });
    }

    const pipeline = document.getElementById('pipelineChart');
    if (pipeline && payload.showPipeline) {
        new Chart(pipeline, {
            type: 'bar',
            data: {
                labels: payload.pipeline.labels,
                datasets: [{
                    data: payload.pipeline.data,
                    backgroundColor: [
                        '#0ea5e9', '#0284c7', '#22d3ee', '#67e8f9',
                        '#38bdf8', '#60a5fa', '#34d399', '#fbbf24', '#f472b6',
                    ],
                    borderRadius: 8,
                }],
            },
            options: baseOptions('bar'),
        });
    }

    if (window.location.hash === '#recent-applications') {
        document.getElementById('recent-applications')?.scrollIntoView({
            behavior: document.documentElement.classList.contains('a11y-reduce-motion') ? 'auto' : 'smooth',
            block: 'start',
        });
    }

    document.querySelectorAll('.dashboard-stat-card').forEach((card) => {
        card.addEventListener('mouseup', () => card.blur());
        card.addEventListener('touchend', () => card.blur(), { passive: true });
    });
});
