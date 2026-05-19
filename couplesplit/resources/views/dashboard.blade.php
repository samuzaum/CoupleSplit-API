<x-app-layout>

<div class="mt-12 pb-20 px-6">

<div class="mx-auto max-w-4xl space-y-6">

@php
$user = auth()->user();
$couple = $user->couples()->first();
@endphp

@if (session('success'))
<div class="bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 p-4 rounded-xl text-sm">
{{ session('success') }}
</div>
@endif

@if (session('error'))
<div class="bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 p-4 rounded-xl text-sm">
{{ session('error') }}
</div>
@endif

@if ($exceededBudgets->isNotEmpty())
<div class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-800 rounded-2xl px-5 py-4">
<div class="flex items-center justify-between gap-4">
    <a href="{{ route('notifications.index') }}" class="flex-1">
        <p class="text-sm font-semibold text-red-600 dark:text-red-400">
            {{ $exceededBudgets->count() }} orçamento{{ $exceededBudgets->count() !== 1 ? 's' : '' }} estourado{{ $exceededBudgets->count() !== 1 ? 's' : '' }} este mês
        </p>
        <p class="text-xs text-red-400 mt-0.5">
            {{ $exceededBudgets->pluck('category')->join(', ') }}
        </p>
    </a>
    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ route('notifications.index') }}" class="text-xs text-red-500">Ver →</a>
        <form method="POST" action="{{ route('notifications.dismiss') }}">
            @csrf
            @foreach($exceededBudgets as $n)
                <input type="hidden" name="keys[]" value="{{ $n['key'] }}">
            @endforeach
            <button type="submit" class="text-xs text-red-400 hover:text-red-600">✕</button>
        </form>
    </div>
</div>
</div>
@endif



{{-- ======================
SALDO PRINCIPAL
====================== --}}
<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl
p-6 sm:p-10
text-center
">

<p class="text-sm text-gray-500 mb-2">
Saldo entre vocês
</p>

@if ($netBalance > 0)

<p class="text-3xl sm:text-5xl font-bold text-green-600 mb-2">
+ R$ {{ number_format($netBalance,2,',','.') }}
</p>

<p class="text-sm text-gray-500">
{{ $partner->name }} te deve
</p>

@elseif ($netBalance < 0)

<p class="text-3xl sm:text-5xl font-bold text-red-600 mb-2">
- R$ {{ number_format(abs($netBalance),2,',','.') }}
</p>

<p class="text-sm text-gray-500">
Você deve {{ $partner->name }}
</p>

@else

<p class="text-3xl font-semibold text-gray-500">
Tudo certo entre vocês
</p>

@endif


<div class="flex justify-center gap-8 mt-6 text-sm flex-wrap">

@php $sharedMonthTotal = round($myMonthShare + $partnerMonthShare, 2); @endphp
<div class="text-center">
<p class="text-xs uppercase tracking-wide text-gray-400 mb-1">Gasto conjunto este mês</p>
<p class="font-semibold text-black dark:text-white">R$ {{ number_format($sharedMonthTotal, 2, ',', '.') }}</p>
@if ($sharedMonthTotal > 0)
<p class="text-xs text-gray-400 mt-1">
    sua parte R$ {{ number_format($myMonthShare, 2, ',', '.') }}
    &middot; {{ $partner->name }} R$ {{ number_format($partnerMonthShare, 2, ',', '.') }}
</p>
@endif
</div>

<div class="text-center">
<p class="text-xs uppercase tracking-wide text-gray-400 mb-1">Dívida líquida</p>
<p class="font-semibold {{ $netBalance < 0 ? 'text-red-500' : 'text-green-600' }}">
{{ $netBalance >= 0 ? 'R$ 0,00' : 'R$ ' . number_format(abs($netBalance), 2, ',', '.') }}
</p>
@if ($balanceBreakdown->isNotEmpty())
<button type="button" onclick="toggleDetail('balance-breakdown')"
    class="text-xs text-gray-400 hover:text-black dark:hover:text-white mt-1">
    ver de onde vem →
</button>
@endif
</div>

@if ($openInstallmentsCount > 0)
<div class="text-center">
<p class="text-xs uppercase tracking-wide text-gray-400 mb-1">Suas parcelas abertas</p>
<p class="font-semibold text-black dark:text-white">
{{ $openInstallmentsCount }} parcela{{ $openInstallmentsCount !== 1 ? 's' : '' }}
</p>
<p class="text-xs text-gray-400 mt-0.5">
    @if ($myDebitInstallmentsCount > 0)
        <span class="text-red-400">{{ $myDebitInstallmentsCount }} você deve</span>
    @endif
    @if ($myDebitInstallmentsCount > 0 && $myCreditInstallmentsCount > 0)
        &middot;
    @endif
    @if ($myCreditInstallmentsCount > 0)
        <span class="text-green-500">{{ $myCreditInstallmentsCount }} a receber</span>
    @endif
</p>
</div>
@endif

</div>

{{-- breakdown expansível do saldo --}}
@if ($balanceBreakdown->isNotEmpty())
<div id="balance-breakdown" class="hidden mt-6 text-left border-t border-gray-100 dark:border-gray-800 pt-4 space-y-3">

    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Despesas compartilhadas este mês</p>

    @foreach ($balanceBreakdown as $b)
    <div class="space-y-0.5">
        <div class="flex justify-between items-start gap-3 text-sm">
            <span class="text-black dark:text-white truncate">{{ $b->description }}</span>
            <span class="shrink-0 text-gray-500">R$ {{ number_format($b->full_amount, 2, ',', '.') }}</span>
        </div>
        <div class="flex justify-between text-xs text-gray-400 gap-3">
            <span>
                Pagou: {{ $b->payer_name }} &middot; split {{ $b->split_pct }}%/{{ 100 - $b->split_pct }}%
            </span>
            <span class="{{ $b->i_owe > 0 ? 'text-red-400' : 'text-green-500' }}">
                @if ($b->i_owe > 0)
                    você deve R$ {{ number_format($b->i_owe, 2, ',', '.') }}
                @else
                    {{ $partner->name }} deve R$ {{ number_format($b->partner_owes, 2, ',', '.') }}
                @endif
            </span>
        </div>
    </div>
    @endforeach

    @php
        $totalIOwe       = round($balanceBreakdown->sum('i_owe'), 2);
        $totalPartnerOwes = round($balanceBreakdown->sum('partner_owes'), 2);
    @endphp
    <div class="pt-2 border-t border-gray-100 dark:border-gray-800 flex justify-between text-sm font-medium">
        <span class="text-gray-500">Resultado bruto</span>
        <span class="{{ $totalIOwe > $totalPartnerOwes ? 'text-red-500' : 'text-green-600' }}">
            @if ($totalIOwe > $totalPartnerOwes)
                você deve R$ {{ number_format($totalIOwe - $totalPartnerOwes, 2, ',', '.') }}
            @else
                {{ $partner->name }} deve R$ {{ number_format($totalPartnerOwes - $totalIOwe, 2, ',', '.') }}
            @endif
        </span>
    </div>

</div>
@endif

<div class="flex justify-center gap-3 mt-4 flex-wrap">

<a href="{{ route('payments.create') }}"
class="px-5 py-2 rounded-full bg-black text-white dark:bg-white dark:text-black font-semibold">
Registrar pagamento
</a>

<a href="{{ route('expenses.create') }}"
class="px-5 py-2 rounded-full border border-gray-300 dark:border-gray-700">
Nova despesa
</a>

@if ($netBalance < 0)
@php $debitFormatted = number_format(abs($netBalance), 2, ',', '.'); @endphp
<form method="POST" action="{{ route('debts.settle') }}"
    onsubmit="return confirm('Isso vai quitar sua dívida líquida de R$ {{ $debitFormatted }}. Confirma?')">
@csrf
<button type="submit"
    class="px-5 py-2 rounded-full border border-red-300 dark:border-red-800 text-red-600 dark:text-red-400 text-sm">
    Liquidar tudo
</button>
</form>
@endif

</div>

</div>



{{-- ======================
MEUS CARTOES
====================== --}}
@if ($myCardCosts->isNotEmpty())
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="font-semibold text-black dark:text-white">Meus cartoes — proximo fechamento</h3>
        <a href="{{ route('installments.index') }}" class="text-xs text-gray-400 hover:text-black dark:hover:text-white">ver parcelas →</a>
    </div>

    @foreach ($myCardCosts as $i => $cc)
    <div class="py-3 border-t border-gray-100 dark:border-gray-800 first:border-t-0 first:pt-0 space-y-3">

        {{-- cabeçalho do cartão --}}
        <div class="flex items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="font-medium text-black dark:text-white">{{ $cc->card->name }}</p>
                <p class="text-xs text-gray-400 mt-0.5">fecha {{ $cc->next_closing->format('d/m/Y') }}</p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <div class="text-right">
                    <p class="text-lg font-bold text-black dark:text-white">R$ {{ number_format($cc->my_cost, 2, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">seu custo real</p>
                </div>
                <button type="button"
                    onclick="toggleDetail('card-detail-{{ $i }}')"
                    class="text-xs px-2 py-1 rounded-full border border-gray-300 dark:border-gray-700 text-gray-500 dark:text-gray-400 shrink-0">
                    ver
                </button>
            </div>
        </div>

        {{-- detalhamento colapsável --}}
        <div id="card-detail-{{ $i }}" class="hidden space-y-2 pl-1">
            @foreach ($cc->lines as $line)
            <div class="flex items-start justify-between gap-3 text-sm">
                <div class="min-w-0">
                    <p class="text-black dark:text-white truncate">{{ $line->description }}</p>
                    @if ($line->is_shared)
                    <p class="text-xs text-gray-400">
                        fatura R$ {{ number_format($line->full_amount, 2, ',', '.') }}
                        &middot; sua parte {{ $line->split_pct }}%
                    </p>
                    @else
                    <p class="text-xs text-gray-400">pessoal</p>
                    @endif
                </div>
                <span class="shrink-0 font-medium text-black dark:text-white">
                    R$ {{ number_format($line->my_amount, 2, ',', '.') }}
                </span>
            </div>
            @endforeach

            @if ($cc->partner_share > 0)
            <div class="flex justify-between text-xs text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-800">
                <span>Fatura total: R$ {{ number_format($cc->total_bill, 2, ',', '.') }}</span>
                <span>Parceiro reembolsa: R$ {{ number_format($cc->partner_share, 2, ',', '.') }}</span>
            </div>
            @endif
        </div>

    </div>
    @endforeach

    @php $totalMyCost = $myCardCosts->sum('my_cost'); @endphp
    @if ($myCardCosts->count() > 1)
    <div class="flex justify-between items-center pt-3 border-t border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500">Total dos cartoes</p>
        <p class="font-bold text-black dark:text-white">R$ {{ number_format($totalMyCost, 2, ',', '.') }}</p>
    </div>
    @endif
</div>
@endif

{{-- ======================
CASAL
====================== --}}
@if($couple)

<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl
p-8
">

<div class="flex justify-between items-start mb-4">
<h3 class="font-semibold text-black dark:text-white">
{{ $couple->name ?? 'Seu casal' }}
</h3>
<div class="text-right">
<p class="text-xs text-gray-400 uppercase tracking-wide">Gasto conjunto este mês</p>
<p class="text-lg font-bold text-black dark:text-white">
R$ {{ number_format($sharedMonthTotal, 2, ',', '.') }}
</p>
@if ($lastMonthTotal > 0)
@php $deltaSign = $monthDelta >= 0 ? '+' : ''; @endphp
<p class="text-xs mt-0.5 {{ $monthDelta > 0 ? 'text-red-500' : ($monthDelta < 0 ? 'text-green-600' : 'text-gray-400') }}">
{{ $deltaSign }}R$ {{ number_format(abs($monthDelta), 2, ',', '.') }} vs mês anterior
</p>
@endif
</div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
@foreach($couple->users as $member)
@php $isMe = $member->id === $user->id; @endphp
<div class="bg-gray-50 dark:bg-gray-900 rounded-2xl p-4">
<p class="text-sm font-medium text-black dark:text-white">
{{ $member->name }}
@if($isMe) <span class="text-xs text-gray-400">(você)</span> @endif
</p>
@if($member->monthly_income)
<p class="text-xs text-gray-500 mt-1">
R$ {{ number_format($member->monthly_income, 2, ',', '.') }}/mês
</p>
@if($myRatio !== null)
<p class="text-xs font-semibold mt-1 text-black dark:text-white">
{{ $isMe ? $myRatio : $partnerRatio }}% das despesas
</p>
@endif
@else
<p class="text-xs text-gray-400 mt-1">
Renda não informada —
<a href="{{ route('profile.edit') }}" class="underline">adicionar</a>
</p>
@endif
</div>
@endforeach
</div>

</div>

@endif



{{-- ======================
PRIMEIRA DESPESA (onboarding)
====================== --}}
@if ($recentExpenses->isEmpty())
<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl p-10 text-center
">
<p class="text-2xl font-bold text-black dark:text-white mb-2">Bem-vindos ao CoupleSplit!</p>
<p class="text-sm text-gray-500 mb-6">Nenhuma despesa registrada ainda. Comece adicionando a primeira.</p>
<a href="{{ route('expenses.create') }}"
class="inline-block px-6 py-3 rounded-full bg-black text-white dark:bg-white dark:text-black font-semibold">
Nova despesa
</a>
</div>
@else

{{-- ======================
DESPESAS RECENTES
====================== --}}
<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl p-8
">
<h3 class="font-semibold mb-4 text-black dark:text-white">Despesas recentes</h3>
<ul class="space-y-3 text-sm">
@foreach($recentExpenses as $expense)
<li class="flex justify-between">
<div>
<p class="text-black dark:text-white">{{ $expense->description }}</p>
<p class="text-xs text-gray-500">{{ ($expense->expense_date ?? $expense->created_at)->format('d/m/Y') }}</p>
</div>
<strong>R$ {{ number_format($expense->amount,2,',','.') }}</strong>
</li>
@endforeach
</ul>
</div>

{{-- ======================
DÉBITOS / CRÉDITOS
====================== --}}
@if ($netBalance < 0 && $openDebits->isNotEmpty())
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-8">
<h3 class="font-semibold mb-1 text-black dark:text-white">Dívidas em aberto</h3>
@if ($creditAvailable > 0)
<p class="text-xs text-gray-400 mb-4">
    Já abatendo R$ {{ number_format($creditAvailable, 2, ',', '.') }} em créditos — dívida líquida: R$ {{ number_format(abs($netBalance), 2, ',', '.') }}
</p>
@else
<div class="mb-4"></div>
@endif
<ul class="space-y-3 text-sm">
@foreach ($openDebits as $debit)
@php $remaining = $debit->amount - $debit->used_amount; @endphp
<li class="flex justify-between">
<span>{{ $debit->label ?? 'Saldo' }}</span>
<strong>R$ {{ number_format($remaining,2,',','.') }}</strong>
</li>
@endforeach
</ul>
</div>
@elseif ($netBalance > 0 && $openCredits->isNotEmpty())
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-8">
<h3 class="font-semibold mb-4 text-black dark:text-white">A receber de {{ $partner->name }}</h3>
<ul class="space-y-3 text-sm">
@foreach ($openCredits as $credit)
@php $remaining = $credit->amount - $credit->used_amount; @endphp
<li class="flex justify-between">
<span>{{ $credit->label ?? 'Saldo' }}</span>
<strong class="text-green-600">R$ {{ number_format($remaining,2,',','.') }}</strong>
</li>
@endforeach
</ul>
</div>
@endif


{{-- ======================
ORÇAMENTOS
====================== --}}
@if ($budgets->isNotEmpty())
<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl p-8
">
<h3 class="font-semibold mb-5 text-black dark:text-white">Orçamento mensal</h3>
<div class="space-y-4">
@foreach ($budgets as $budget)
@php
$color = $budget->percentage >= 100 ? 'bg-red-500' : ($budget->percentage >= 75 ? 'bg-yellow-400' : 'bg-green-500');
@endphp
<div>
    <div class="flex justify-between items-baseline gap-2 text-sm mb-1 flex-wrap">
        <span class="font-medium text-black dark:text-white">{{ $budget->category }}</span>
        <span class="text-gray-500 text-xs shrink-0">
            R$ {{ number_format($budget->spent, 2, ',', '.') }}
            / R$ {{ number_format($budget->amount, 2, ',', '.') }}
            ({{ $budget->percentage }}%)
        </span>
    </div>
    <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2">
        <div class="{{ $color }} h-2 rounded-full transition-all" style="width: {{ $budget->percentage }}%"></div>
    </div>
</div>
@endforeach
</div>
</div>
@endif

{{-- ======================
GRÁFICOS
====================== --}}
@if ($byMonth->sum() > 0)
<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl p-8
">
<h3 class="font-semibold mb-6 text-black dark:text-white">Sua parte por mês</h3>
<canvas id="chartByMonth" height="100"></canvas>
</div>
@endif

@if ($byCategory->isNotEmpty())
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-6">
<h3 class="font-semibold mb-4 text-black dark:text-white">Sua parte por categoria</h3>
<div class="mx-auto" style="max-width:260px">
    <canvas id="chartByCategory"></canvas>
</div>
</div>
@endif

@endif


</div>

</div>

@if ($recentExpenses->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function () {
    var monthLabels = @json($byMonth->keys());
    var monthValues = @json($byMonth->values());
    var catLabels   = @json($byCategory->keys());
    var catValues   = @json($byCategory->values());

    var isDark = document.documentElement.classList.contains('dark');
    var gridColor  = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
    var tickColor  = isDark ? '#9ca3af' : '#6b7280';
    var barColor   = isDark ? 'rgba(255,255,255,0.80)' : 'rgba(0,0,0,0.75)';
    var donutLight = ['#1a1a1a','#3d3d3d','#5e5e5e','#7f7f7f','#a0a0a0','#c1c1c1','#d4d4d4','#e8e8e8'];
    var donutDark  = ['#ffffff','#d1d5db','#9ca3af','#6b7280','#4b5563','#374151','#1f2937','#111827'];
    var donutColors = isDark ? donutDark : donutLight;
    var legendColor = isDark ? '#d1d5db' : '#374151';

    if (document.getElementById('chartByMonth')) {
        new Chart(document.getElementById('chartByMonth'), {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [{ label: 'Total (R$)', data: monthValues, backgroundColor: barColor, borderRadius: 6 }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor } },
                    x: { grid: { display: false }, ticks: { color: tickColor } }
                }
            }
        });
    }

    if (document.getElementById('chartByCategory')) {
        new Chart(document.getElementById('chartByCategory'), {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{ data: catValues, backgroundColor: donutColors, borderWidth: 0 }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 11 }, color: legendColor, padding: 12 }
                    }
                },
                maintainAspectRatio: true
            }
        });
    }
})();
</script>
@endif

<script>
function toggleDetail(id) {
    var el = document.getElementById(id);
    if (el) el.classList.toggle('hidden');
}
</script>

</x-app-layout>