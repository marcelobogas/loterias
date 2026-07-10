import Chart from 'chart.js/auto';

// Livewire ships and starts its own Alpine instance, so we hook into
// `alpine:init` instead of importing/starting a second one.
window.Chart = Chart;

const chartTheme = {
    grid: 'rgba(148, 163, 184, 0.1)',
    ticks: '#94a3b8',
};

window.renderBarChart = function (canvas, labels, data, color = '#10b981') {
    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{ data, backgroundColor: color, borderRadius: 4, maxBarThickness: 28 }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: chartTheme.ticks } },
                y: { grid: { color: chartTheme.grid }, ticks: { color: chartTheme.ticks, precision: 0 } },
            },
        },
    });
};
