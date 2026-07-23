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
            legend: type === 'doughnut'
                ? { position: 'right', labels: { boxWidth: 12, padding: 12, color } }
                : { display: false },
        },
        scales: type !== 'doughnut'
            ? {
                y: { beginAtZero: true, ticks: { precision: 0, color }, grid: { color: grid } },
                x: { ticks: { color }, grid: { display: false } },
            }
            : undefined,
    };
}

const ROLE_COLORS = [
    '#0f766e', '#0284c7', '#0ea5e9', '#0891b2',
    '#6366f1', '#8b5cf6', '#22d3ee', '#34d399',
    '#f59e0b', '#f472b6', '#64748b',
];

document.addEventListener('DOMContentLoaded', async () => {
    const payloadEl = document.getElementById('admin-dashboard-chart-data');
    if (!payloadEl) {
        return;
    }

    const payload = JSON.parse(payloadEl.textContent);
    const { default: Chart } = await import('chart.js/auto');
    const { color, grid } = chartTheme();

    Chart.defaults.color = color;
    Chart.defaults.borderColor = grid;

    const rolesCanvas = document.getElementById('adminRolesChart');
    if (rolesCanvas && payload.roles?.labels?.length) {
        new Chart(rolesCanvas, {
            type: 'doughnut',
            data: {
                labels: payload.roles.labels,
                datasets: [{
                    label: payload.roles.label,
                    data: payload.roles.data,
                    backgroundColor: ROLE_COLORS.slice(0, payload.roles.labels.length),
                    borderWidth: 0,
                    hoverOffset: 6,
                }],
            },
            options: baseOptions('doughnut'),
        });
    }

    const auditCanvas = document.getElementById('adminAuditChart');
    if (auditCanvas && payload.canViewAudit) {
        new Chart(auditCanvas, {
            type: 'bar',
            data: {
                labels: payload.audit.labels,
                datasets: [{
                    label: payload.audit.label,
                    data: payload.audit.data,
                    backgroundColor: '#0284c7',
                    borderRadius: 8,
                }],
            },
            options: baseOptions('bar'),
        });
    }

    document.querySelectorAll('.dashboard-stat-card').forEach((card) => {
        card.addEventListener('mouseup', () => card.blur());
        card.addEventListener('touchend', () => card.blur(), { passive: true });
    });
});
