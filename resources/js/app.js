import Chart from 'chart.js/auto';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

// Livewire ships and starts its own Alpine instance, so we hook into
// `alpine:init` instead of importing/starting a second one.
window.Chart = Chart;

// Project-wide default for flash messages: any Livewire component dispatches
// `flash` (message, and optionally type: 'success'|'error'|...) to show a toast.
document.addEventListener('livewire:init', () => {
    Livewire.on('flash', ({ message, type = 'success' }) => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: type,
            title: message,
            background: '#0f172a',
            color: '#e2e8f0',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    });
});

// Project-wide default for destructive-action confirmations, replacing
// wire:confirm (native browser confirm()). Used from Blade via
// x-on:click="swalConfirm('...', () => $wire.someMethod())".
window.swalConfirm = function (text, onConfirm) {
    Swal.fire({
        title: 'Tem certeza?',
        text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#334155',
        background: '#0f172a',
        color: '#e2e8f0',
    }).then((result) => {
        if (result.isConfirmed) onConfirm();
    });
};

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
