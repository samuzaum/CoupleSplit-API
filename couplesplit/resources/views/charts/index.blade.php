<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-4xl space-y-8">

<h1 class="text-3xl font-bold text-black dark:text-white">Gráficos</h1>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

{{-- Gastos por mês --}}
<div class="border border-gray-200 dark:border-gray-800 rounded-3xl p-8">
<h2 class="font-semibold text-black dark:text-white mb-6">Gastos por mês</h2>
<canvas id="chartMonth"></canvas>
</div>

{{-- Gastos por categoria --}}
<div class="border border-gray-200 dark:border-gray-800 rounded-3xl p-8">
<h2 class="font-semibold text-black dark:text-white mb-6">Gastos por categoria</h2>
@if ($byCategory->isEmpty())
<p class="text-sm text-gray-500">Nenhuma despesa com categoria registrada.</p>
@else
<canvas id="chartCategory"></canvas>
@endif
</div>

</div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
const isDark = document.documentElement.classList.contains('dark');
const textColor = isDark ? '#fff' : '#000';

// Gastos por mês
new Chart(document.getElementById('chartMonth'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($byMonth->keys()) !!},
        datasets: [{
            label: 'R$',
            data: {!! json_encode($byMonth->values()) !!},
            backgroundColor: 'rgba(0,0,0,0.7)',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { ticks: { color: textColor }, grid: { color: 'rgba(128,128,128,0.1)' } },
            x: { ticks: { color: textColor }, grid: { display: false } },
        }
    }
});

@if ($byCategory->isNotEmpty())
// Gastos por categoria
new Chart(document.getElementById('chartCategory'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($byCategory->keys()) !!},
        datasets: [{
            data: {!! json_encode($byCategory->values()) !!},
            backgroundColor: [
                '#000','#333','#555','#777','#999','#bbb','#ddd','#eee'
            ],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { color: textColor, padding: 12 }
            }
        }
    }
});
@endif
</script>

</x-app-layout>
