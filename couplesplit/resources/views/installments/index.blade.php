<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-2xl space-y-6">

<h1 class="text-3xl font-bold text-black dark:text-white">Parcelas em aberto</h1>

@if ($installments->isEmpty())
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-8 text-center">
    <p class="text-gray-500 text-sm">Nenhuma parcela em aberto.</p>
</div>
@else

{{-- resumo --}}
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl px-6 py-5 flex justify-between items-center">
    <div>
        <p class="text-xs text-gray-400 uppercase tracking-wide">Total em aberto</p>
        <p class="text-2xl font-bold text-black dark:text-white mt-0.5">
            R$ {{ number_format($totalOpen, 2, ',', '.') }}
        </p>
    </div>
    <div class="text-right">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Parcelas</p>
        <p class="text-2xl font-bold text-black dark:text-white mt-0.5">{{ $installments->count() }}</p>
    </div>
</div>

{{-- agrupadas por mês de vencimento --}}
@php
    $grouped = $installments->groupBy(fn($i) => $i->due_date->format('Y-m'));
@endphp

@foreach ($grouped as $ym => $items)
@php
    $label    = \Carbon\Carbon::createFromFormat('Y-m', $ym)->translatedFormat('F \d\e Y');
    $monthSum = $items->sum('amount');
    $isPast   = \Carbon\Carbon::createFromFormat('Y-m', $ym)->lt(\Carbon\Carbon::now()->startOfMonth());
    $isCurrent = \Carbon\Carbon::createFromFormat('Y-m', $ym)->isSameMonth(\Carbon\Carbon::now());
@endphp

<div class="space-y-2">

    <div class="flex items-center justify-between px-1">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
            {{ $label }}
            @if ($isCurrent)
                <span class="ml-2 bg-black dark:bg-white text-white dark:text-black text-[10px] px-2 py-0.5 rounded-full">este mês</span>
            @elseif ($isPast)
                <span class="ml-2 text-red-400">vencido</span>
            @endif
        </p>
        <p class="text-xs text-gray-500">R$ {{ number_format($monthSum, 2, ',', '.') }}</p>
    </div>

    @foreach ($items as $installment)
    @php
        $isOverdue = $installment->due_date->isPast();
        $isMe      = $installment->expense->paid_by === auth()->id();
    @endphp

    <div class="bg-white dark:bg-black border {{ $isOverdue ? 'border-red-300 dark:border-red-800' : 'border-gray-200 dark:border-gray-800' }} rounded-2xl px-5 py-4 flex items-center justify-between gap-4">

        <div class="flex-1 min-w-0">
            <p class="font-medium text-black dark:text-white truncate">
                {{ $installment->expense->description }}
            </p>
            <p class="text-xs text-gray-500 mt-0.5">
                Parcela {{ $installment->installment_number }}
                · {{ $installment->expense->payer->name }}
                · vence {{ $installment->due_date->format('d/m/Y') }}
                @if ($isOverdue)
                    · <span class="text-red-500 font-medium">em atraso</span>
                @endif
            </p>
            @if ($installment->expense->notes)
            <p class="text-xs text-gray-400 mt-0.5 italic truncate">{{ $installment->expense->notes }}</p>
            @endif
        </div>

        <strong class="text-black dark:text-white shrink-0">
            R$ {{ number_format($installment->amount, 2, ',', '.') }}
        </strong>

    </div>
    @endforeach

</div>
@endforeach

@endif

</div>
</div>

</x-app-layout>
