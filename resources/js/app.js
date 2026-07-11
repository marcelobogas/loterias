import Chart from 'chart.js/auto';

// Livewire ships and starts its own Alpine instance, so we hook into
// `alpine:init` instead of importing/starting a second one.
window.Chart = Chart;

const chartTheme = {
    grid: 'rgba(148, 163, 184, 0.1)',
    ticks: '#94a3b8',
};

window.renderComparisonBarChart = function (canvas, labels, observed, expected, observedLabel, expectedLabel) {
    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: observedLabel, data: observed, backgroundColor: '#10b981', borderRadius: 4, maxBarThickness: 22 },
                { label: expectedLabel, data: expected, backgroundColor: 'rgba(14, 165, 233, 0.55)', borderRadius: 4, maxBarThickness: 22 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: true, labels: { color: chartTheme.ticks } } },
            scales: {
                x: { grid: { display: false }, ticks: { color: chartTheme.ticks } },
                y: { grid: { color: chartTheme.grid }, ticks: { color: chartTheme.ticks } },
            },
        },
    });
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
