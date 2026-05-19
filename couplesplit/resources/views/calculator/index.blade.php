<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-2xl space-y-6">

<div>
<h1 class="text-3xl font-bold text-black dark:text-white">Simulador de compra</h1>
<p class="text-sm text-gray-500 mt-1">Veja quanto da renda ja esta comprometida e como uma nova compra mexe nos proximos meses.</p>
</div>

@php
$usedPct = $myIncome > 0 ? round($committed / $myIncome * 100) : null;
$barClass = fn($c) => match($c) {
    'green' => 'bg-green-500',
    'yellow' => 'bg-yellow-400',
    'red' => 'bg-red-500',
    default => 'bg-gray-300',
};
$textClass = fn($c) => match($c) {
    'green' => 'text-green-600',
    'yellow' => 'text-yellow-600',
    'red' => 'text-red-600',
    default => 'text-gray-500',
};
$currentColor = $usedPct === null ? 'gray' : ($usedPct < 50 ? 'green' : ($usedPct < 75 ? 'yellow' : 'red'));
$expenseCommitted = round($committed - $netDebt, 2);
@endphp

@if ($myIncome)
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-4">
    <div class="flex justify-between items-start gap-4">
        <div>
            <p class="text-sm font-medium text-black dark:text-white">Seu mes atual</p>
            <p class="text-xs text-gray-500 mt-1">Inclui faturas/parcelas abertas, despesas do mes e recorrencias.</p>
        </div>
        <div class="text-right">
            <p class="text-lg font-bold {{ ($available ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                R$ {{ number_format(abs($available ?? 0), 2, ',', '.') }}
            </p>
            <p class="text-xs text-gray-500">{{ ($available ?? 0) < 0 ? 'acima da renda' : 'ainda livre' }}</p>
        </div>
    </div>
    <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-3 overflow-hidden">
        <div class="{{ $barClass($currentColor) }} h-3 rounded-full" style="width: {{ min(100, $usedPct ?? 0) }}%"></div>
    </div>
    <div class="space-y-1 text-xs text-gray-400">
        <div class="flex justify-between">
            <span>Despesas do mes: R$ {{ number_format($expenseCommitted, 2, ',', '.') }}</span>
            <span>Sua renda: R$ {{ number_format($myIncome, 2, ',', '.') }}</span>
        </div>
        @if ($netDebt > 0)
        <div class="flex justify-between text-red-500">
            <span>Divida com parceiro(a): R$ {{ number_format($netDebt, 2, ',', '.') }}</span>
            <span>Total comprometido: {{ $usedPct }}%</span>
        </div>
        @else
        <div class="flex justify-between">
            <span>Total comprometido: R$ {{ number_format($committed, 2, ',', '.') }} ({{ $usedPct }}%)</span>
        </div>
        @endif
    </div>
</div>
@else
<div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-2xl p-4 text-sm text-yellow-800 dark:text-yellow-200">
    Cadastre a renda no perfil para ver risco em percentual. A simulacao ainda mostra os valores comprometidos.
</div>
@endif

<form method="GET" action="{{ route('calculator.index') }}" class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-8 space-y-5">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor total da compra</label>
        <input name="amount" type="number" step="0.01" min="0.01" value="{{ request('amount') }}" placeholder="Ex: 2400.00" required class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white" />
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Numero de parcelas</label>
        <input name="installments" type="number" min="1" max="48" value="{{ request('installments', 1) }}" placeholder="Ex: 12" required class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white" />
    </div>
    @if (request('amount') && request('installments'))
    <div class="text-sm text-gray-500">
        Parcela mensal: <strong class="text-black dark:text-white">R$ {{ number_format(request('amount') / request('installments'), 2, ',', '.') }}</strong>
    </div>
    @endif
    <button type="submit" class="w-full py-3 rounded-full font-semibold bg-black text-white dark:bg-white dark:text-black hover:opacity-90 transition">Simular</button>
</form>

@if ($simulation)
@php
$worst = $simulation->max(fn($m) => match($m['color']) {'red' => 3, 'yellow' => 2, 'green' => 1, default => 0});
$verdict = match($worst) {
    3 => ['Compra arriscada', 'Um ou mais meses passam de 75% da renda.', 'text-red-600'],
    2 => ['Compra apertada', 'Alguns meses ficam entre 50% e 75% da renda.', 'text-yellow-600'],
    1 => ['Compra tranquila', 'A compra cabe na margem atual.', 'text-green-600'],
    default => ['Simulacao sem renda', 'Cadastre renda para medir o risco.', 'text-gray-500'],
};
@endphp

<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl p-6 text-center">
    <p class="text-xl font-bold {{ $verdict[2] }}">{{ $verdict[0] }}</p>
    <p class="text-sm text-gray-500 mt-1">{{ $verdict[1] }}</p>
</div>

@if ($cashComparison)
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-4">
    <h3 class="text-sm font-semibold text-black dark:text-white">Impacto no primeiro mes</h3>
    <div class="grid sm:grid-cols-2 gap-4">
        @foreach ([['label' => 'A vista', 'impact' => 'cash_impact', 'risk' => 'cash_risk', 'color' => 'cash_color', 'available' => 'cash_available'], ['label' => $cashComparison['installments'] . 'x parcelado', 'impact' => 'install_impact', 'risk' => 'install_risk', 'color' => 'install_color', 'available' => 'install_available']] as $option)
        <div class="border border-gray-100 dark:border-gray-800 rounded-xl p-4 space-y-3">
            <div class="flex justify-between gap-3">
                <p class="text-xs uppercase tracking-wide text-gray-400">{{ $option['label'] }}</p>
                @if ($cashComparison[$option['available']] !== null)
                <p class="text-xs {{ $cashComparison[$option['available']] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    R$ {{ number_format(abs($cashComparison[$option['available']]), 2, ',', '.') }} {{ $cashComparison[$option['available']] >= 0 ? 'livre' : 'acima' }}
                </p>
                @endif
            </div>
            <p class="text-lg font-bold {{ $textClass($cashComparison[$option['color']]) }}">R$ {{ number_format($cashComparison[$option['impact']], 2, ',', '.') }}</p>
            @if ($cashComparison[$option['risk']] !== null)
            <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-3 overflow-hidden">
                <div class="{{ $barClass($cashComparison[$option['color']]) }} h-3 rounded-full" style="width: {{ min(100, $cashComparison[$option['risk']]) }}%"></div>
            </div>
            <p class="text-xs text-gray-500">{{ $cashComparison[$option['risk']] }}% da renda comprometida</p>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
    <div class="grid grid-cols-4 px-5 py-3 text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100 dark:border-gray-900">
        <span>Mes</span>
        <span class="text-right">Atual</span>
        <span class="text-right">Nova</span>
        <span class="text-right">Livre</span>
    </div>
    @foreach ($simulation as $row)
    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-900 last:border-0">
        <div class="grid grid-cols-4 items-center gap-2 mb-2">
            <span class="text-sm font-medium text-black dark:text-white capitalize">{{ $row['month']->translatedFormat('M/y') }}</span>
            <span class="text-sm text-right text-gray-500">R$ {{ number_format($row['committed'], 2, ',', '.') }}</span>
            <span class="text-sm text-right {{ $row['new_charge'] > 0 ? 'text-black dark:text-white font-medium' : 'text-gray-300' }}">{{ $row['new_charge'] > 0 ? 'R$ ' . number_format($row['new_charge'], 2, ',', '.') : '-' }}</span>
            <span class="text-sm text-right font-semibold {{ $row['available'] === null ? 'text-gray-400' : ($row['available'] >= 0 ? 'text-green-600' : 'text-red-600') }}">
                {{ $row['available'] === null ? 'R$ ' . number_format($row['total'], 2, ',', '.') : 'R$ ' . number_format(abs($row['available']), 2, ',', '.') }}
                @if ($row['risk_pct'] !== null)<span class="block text-xs font-normal">{{ $row['risk_pct'] }}%</span>@endif
            </span>
        </div>
        @if ($myIncome && $row['risk_pct'] !== null)
        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
            <div class="{{ $barClass($row['color']) }} h-2 rounded-full transition-all" style="width: {{ min(100, $row['risk_pct']) }}%"></div>
        </div>
        @endif
    </div>
    @endforeach
</div>
@endif

</div>
</div>

</x-app-layout>