<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-3xl space-y-6">

<h1 class="text-3xl font-bold text-black dark:text-white">Parcelas</h1>

@if ($faturas->isNotEmpty())
<div class="space-y-3">
<p class="text-xs font-semibold uppercase tracking-wide text-gray-400 px-1">Proxima fatura por cartao</p>
@foreach ($faturas as $fatura)
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl px-5 py-4 space-y-3">
    <div class="flex items-center justify-between">
        <div>
            <p class="font-semibold text-black dark:text-white">{{ $fatura->card->name }}</p>
            <p class="text-xs text-gray-400 mt-0.5">fecha {{ $fatura->next_closing->format('d/m/Y') }}</p>
        </div>
        <div class="text-right">
            <p class="text-lg font-bold text-black dark:text-white">R$ {{ number_format($fatura->total, 2, ',', '.') }}</p>
            <p class="text-xs text-gray-400">{{ $fatura->installments->count() }} parcela(s)</p>
        </div>
    </div>
    <div class="w-full border-t border-gray-100 dark:border-gray-800"></div>
    <div class="space-y-2">
    @foreach ($fatura->single_expenses as $exp)
    <div class="flex items-center justify-between text-sm gap-4">
        <div class="min-w-0">
            <p class="text-black dark:text-white truncate">{{ $exp->description }}</p>
            <p class="text-xs text-gray-400">avulso{{ $exp->is_shared ? ' · compartilhado' : '' }}</p>
        </div>
        <span class="shrink-0 text-gray-700 dark:text-gray-300">R$ {{ number_format($exp->amount, 2, ',', '.') }}</span>
    </div>
    @endforeach
    @foreach ($fatura->installments as $inst)
    <div class="flex items-center justify-between text-sm gap-4">
        <div class="min-w-0">
            <p class="text-black dark:text-white truncate">{{ $inst->expense->description }}</p>
            <p class="text-xs text-gray-400">parcela {{ $inst->installment_number }}</p>
        </div>
        <span class="shrink-0 text-gray-700 dark:text-gray-300">R$ {{ number_format($inst->amount, 2, ',', '.') }}</span>
    </div>
    @endforeach
    </div>
</div>
@endforeach
</div>
@endif

@if ($installments->isEmpty())
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl p-8 text-center">
    <p class="text-gray-500 text-sm">Nenhuma parcela registrada.</p>
</div>
@else

<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl px-6 py-5 flex justify-between items-center">
    <div>
        <p class="text-xs text-gray-400 uppercase tracking-wide">Total em aberto</p>
        <p class="text-2xl font-bold text-black dark:text-white mt-0.5">R$ {{ number_format($totalOpen, 2, ',', '.') }}</p>
    </div>
    <div class="text-right">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Abertas</p>
        <p class="text-2xl font-bold text-black dark:text-white mt-0.5">{{ $openInstallments->count() }}</p>
    </div>
</div>

@php $grouped = $installments->groupBy(fn($i) => $i->due_date->format('Y-m')); @endphp

@foreach ($grouped as $ym => $items)
@php
    $month = \Carbon\Carbon::createFromFormat('Y-m', $ym);
    $label = $month->translatedFormat('F \d\e Y');
    $monthOpen = $items->whereNull('paid_at')->sum('amount');
    $isPast = $month->lt(\Carbon\Carbon::now()->startOfMonth());
    $isCurrent = $month->isSameMonth(\Carbon\Carbon::now());
@endphp

<div class="space-y-2">
    <div class="flex items-center justify-between px-1">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
            {{ $label }}
            @if ($isCurrent)
                <span class="ml-2 bg-black dark:bg-white text-white dark:text-black text-[10px] px-2 py-0.5 rounded-full">este mes</span>
            @elseif ($isPast && $monthOpen > 0)
                <span class="ml-2 text-red-500">com pendencia</span>
            @endif
        </p>
        <p class="text-xs text-gray-500">Aberto: R$ {{ number_format($monthOpen, 2, ',', '.') }}</p>
    </div>

    @foreach ($items as $installment)
    @php
        $isPaid = $installment->paid_at !== null;
        $canMarkPaid = !$isPaid && $installment->due_date->lte(now());
        $isOverdue = !$isPaid && $installment->due_date->isPast();
        $isShared = (bool) $installment->expense->is_shared;
    @endphp

    <div class="bg-white dark:bg-black border {{ $isOverdue ? 'border-red-300 dark:border-red-800' : 'border-gray-200 dark:border-gray-800' }} rounded-2xl px-5 py-4 flex items-center justify-between gap-4">
        <div class="flex-1 min-w-0">
            <p class="font-medium text-black dark:text-white truncate">{{ $installment->expense->description }}</p>
            <p class="text-xs text-gray-500 mt-0.5">
                Parcela {{ $installment->installment_number }}
                &middot; {{ $installment->expense->payer->name }}
                &middot; vence {{ $installment->due_date->format('d/m/Y') }}
                @if ($isPaid)
                    &middot; <span class="text-green-600 font-medium">paga em {{ $installment->paid_at->format('d/m/Y') }}</span>
                @elseif ($isOverdue)
                    &middot; <span class="text-red-500 font-medium">em atraso</span>
                @endif
            </p>
            @if ($installment->expense->notes)
            <p class="text-xs text-gray-400 mt-0.5 italic truncate">{{ $installment->expense->notes }}</p>
            @endif
        </div>

        <div class="text-right shrink-0 space-y-2">
            <strong class="block text-black dark:text-white">R$ {{ number_format($installment->amount, 2, ',', '.') }}</strong>
            @if ($isPaid)
            <form method="POST" action="{{ route('installments.mark-unpaid', $installment) }}">
                @csrf
                <button class="text-xs px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300">Reabrir</button>
            </form>
            @elseif ($canMarkPaid)
            <form method="POST" action="{{ route('installments.mark-paid', $installment) }}"
                @if ($isShared)
                    onsubmit="return confirm('Use apenas se esta parcela compartilhada ja foi quitada antes do cadastro. Ela saira das dividas em aberto sem registrar um novo pagamento entre o casal.')"
                @endif>
                @csrf
                <button class="text-xs px-3 py-1 rounded-full bg-black text-white dark:bg-white dark:text-black">
                    {{ $isShared ? 'Ja quitada' : 'Marcar paga' }}
                </button>
            </form>
            @else
            <span class="text-xs text-gray-400">Aguardando vencimento</span>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endforeach

@endif

</div>
</div>

</x-app-layout>
