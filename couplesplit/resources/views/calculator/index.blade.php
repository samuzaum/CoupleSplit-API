<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-2xl space-y-6">

<div>
<h1 class="text-3xl font-bold text-black dark:text-white">Simulador de compra</h1>
<p class="text-sm text-gray-500 mt-1">Veja o impacto de uma compra parcelada no seu orçamento dos próximos meses.</p>
</div>

{{-- capacidade atual --}}
@if ($totalIncome)
@php
$usedPct  = $totalIncome > 0 ? round($committed / $totalIncome * 100) : 0;
$availPct = 100 - $usedPct;
$barColor = $usedPct >= 75 ? 'bg-red-500' : ($usedPct >= 50 ? 'bg-yellow-400' : 'bg-green-500');
@endphp
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-3">
    <div class="flex justify-between items-center">
        <p class="text-sm font-medium text-black dark:text-white">Margem disponível este mês</p>
        <p class="text-lg font-bold {{ $available >= 0 ? 'text-green-600' : 'text-red-600' }}">
            R$ {{ number_format(abs($available), 2, ',', '.') }}
            {{ $available < 0 ? 'negativo' : 'livres' }}
        </p>
    </div>
    <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2">
        <div class="{{ $barColor }} h-2 rounded-full" style="width: {{ min(100, $usedPct) }}%"></div>
    </div>
    <div class="flex justify-between text-xs text-gray-400">
        <span>Comprometido: R$ {{ number_format($committed, 2, ',', '.') }} ({{ $usedPct }}%)</span>
        <span>Renda: R$ {{ number_format($totalIncome, 2, ',', '.') }}</span>
    </div>
</div>
@endif

@if (!$totalIncome)
<div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-2xl p-4 text-sm text-yellow-800 dark:text-yellow-200">
    Para ver o risco em percentual, cadastre a renda de ambos no
    <a href="{{ route('profile.edit') }}" class="underline font-medium">perfil</a>.
    A simulação ainda funciona mas mostrará só os valores absolutos.
</div>
@endif

{{-- formulário --}}
<form method="GET" action="{{ route('calculator.index') }}"
    class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-8 space-y-5">

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor total da compra</label>
        <input name="amount" type="number" step="0.01" min="0.01"
            value="{{ request('amount') }}"
            placeholder="Ex: 2400.00" required
            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white"
        />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número de parcelas</label>
        <input name="installments" type="number" min="1" max="48"
            value="{{ request('installments', 1) }}"
            placeholder="Ex: 12" required
            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white"
        />
    </div>

    @if (request('amount') && request('installments'))
    <div class="text-sm text-gray-500">
        Parcela mensal:
        <strong class="text-black dark:text-white">
            R$ {{ number_format(request('amount') / request('installments'), 2, ',', '.') }}
        </strong>
    </div>
    @endif

    <button type="submit"
        class="w-full py-3 rounded-full font-semibold bg-black text-white dark:bg-white dark:text-black hover:opacity-90 transition">
        Simular
    </button>
</form>


{{-- resultado --}}
@if ($simulation)

@php
$worstColor = $simulation->max(fn($m) => match($m['color']) {
    'red'    => 3,
    'yellow' => 2,
    'green'  => 1,
    default  => 0,
});
$verdict = match($worstColor) {
    3 => ['text' => 'Compra arriscada', 'sub' => 'Um ou mais meses ficam acima de 75% da renda.', 'class' => 'text-red-600'],
    2 => ['text' => 'Compra apertada', 'sub' => 'Alguns meses ficam entre 50% e 75% da renda.', 'class' => 'text-yellow-600'],
    1 => ['text' => 'Compra tranquila', 'sub' => 'Nenhum mês ultrapassa 50% da renda.', 'class' => 'text-green-600'],
    default => ['text' => 'Simulação sem renda', 'sub' => 'Cadastre sua renda para ver o risco em percentual.', 'class' => 'text-gray-500'],
};
@endphp

@if ($totalIncome)
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl p-6 text-center">
    <p class="text-xl font-bold {{ $verdict['class'] }}">{{ $verdict['text'] }}</p>
    <p class="text-sm text-gray-500 mt-1">{{ $verdict['sub'] }}</p>
</div>
@endif

{{-- comparador à vista vs parcelado --}}
@if ($cashComparison)
@php
$colorClass = fn($c) => match($c) {
    'green'  => 'text-green-600',
    'yellow' => 'text-yellow-600',
    'red'    => 'text-red-600',
    default  => 'text-gray-400',
};
$barClass = fn($c) => match($c) {
    'green'  => 'bg-green-500',
    'yellow' => 'bg-yellow-400',
    'red'    => 'bg-red-500',
    default  => 'bg-gray-300',
};
@endphp
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
    <h3 class="text-sm font-semibold text-black dark:text-white mb-4">À vista vs Parcelado — impacto no 1º mês</h3>
    <div class="grid grid-cols-2 gap-4">

        {{-- à vista --}}
        <div class="border border-gray-100 dark:border-gray-800 rounded-xl p-4 space-y-2">
            <p class="text-xs uppercase tracking-wide text-gray-400">À vista</p>
            <p class="text-lg font-bold {{ $colorClass($cashComparison['cash_color']) }}">
                R$ {{ number_format($cashComparison['cash_impact'], 2, ',', '.') }}
            </p>
            @if ($cashComparison['cash_risk'] !== null)
            <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5">
                <div class="{{ $barClass($cashComparison['cash_color']) }} h-1.5 rounded-full"
                    style="width: {{ min(100, $cashComparison['cash_risk']) }}%"></div>
            </div>
            <p class="text-xs text-gray-500">{{ $cashComparison['cash_risk'] }}% da renda</p>
            @endif
            <p class="text-xs text-gray-400 mt-1">Paga R$ {{ number_format($cashComparison['amount'], 2, ',', '.') }} de uma vez</p>
        </div>

        {{-- parcelado --}}
        <div class="border border-gray-100 dark:border-gray-800 rounded-xl p-4 space-y-2">
            <p class="text-xs uppercase tracking-wide text-gray-400">{{ $cashComparison['installments'] }}x parcelado</p>
            <p class="text-lg font-bold {{ $colorClass($cashComparison['install_color']) }}">
                R$ {{ number_format($cashComparison['install_impact'], 2, ',', '.') }}
            </p>
            @if ($cashComparison['install_risk'] !== null)
            <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5">
                <div class="{{ $barClass($cashComparison['install_color']) }} h-1.5 rounded-full"
                    style="width: {{ min(100, $cashComparison['install_risk']) }}%"></div>
            </div>
            <p class="text-xs text-gray-500">{{ $cashComparison['install_risk'] }}% da renda</p>
            @endif
            <p class="text-xs text-gray-400 mt-1">R$ {{ number_format($cashComparison['monthly'], 2, ',', '.') }}/mês por {{ $cashComparison['installments'] }} meses</p>
        </div>

    </div>
</div>
@endif

<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
    <div class="min-w-[360px]">
    <div class="grid grid-cols-4 px-5 py-3 text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100 dark:border-gray-900">
        <span>Mês</span>
        <span class="text-right">Fixo</span>
        <span class="text-right">Parcela</span>
        <span class="text-right">Total</span>
    </div>

    @foreach ($simulation as $row)
    @php
    $barColor = match($row['color']) {
        'green'  => 'bg-green-500',
        'yellow' => 'bg-yellow-400',
        'red'    => 'bg-red-500',
        default  => 'bg-gray-300',
    };
    $textColor = match($row['color']) {
        'green'  => 'text-green-600',
        'yellow' => 'text-yellow-600',
        'red'    => 'text-red-600',
        default  => 'text-gray-400',
    };
    @endphp

    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-900 last:border-0">
        <div class="grid grid-cols-4 items-center mb-2">
            <span class="text-sm font-medium text-black dark:text-white capitalize">
                {{ $row['month']->translatedFormat('M/y') }}
            </span>
            <span class="text-sm text-right text-gray-500">
                R$ {{ number_format($row['committed'], 2, ',', '.') }}
            </span>
            <span class="text-sm text-right {{ $row['new_charge'] > 0 ? 'text-black dark:text-white font-medium' : 'text-gray-300' }}">
                {{ $row['new_charge'] > 0 ? 'R$ ' . number_format($row['new_charge'], 2, ',', '.') : '—' }}
            </span>
            <span class="text-sm text-right font-semibold {{ $textColor }}">
                R$ {{ number_format($row['total'], 2, ',', '.') }}
                @if ($row['risk_pct'] !== null)
                <span class="block text-xs font-normal">{{ $row['risk_pct'] }}%</span>
                @endif
            </span>
        </div>

        @if ($totalIncome && $row['risk_pct'] !== null)
        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5">
            <div class="{{ $barColor }} h-1.5 rounded-full transition-all" style="width: {{ min(100, $row['risk_pct']) }}%"></div>
        </div>
        @endif
    </div>
    @endforeach

    </div>{{-- min-w --}}
    </div>{{-- overflow-x-auto --}}
</div>

@if ($totalIncome)
<p class="text-xs text-gray-400 text-center">
    Renda combinada do casal: R$ {{ number_format($totalIncome, 2, ',', '.') }}/mês
</p>
@endif

@endif

</div>
</div>

</x-app-layout>
