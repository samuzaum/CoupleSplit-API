<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-2xl space-y-4">

<h1 class="text-3xl font-bold text-black dark:text-white">Notificações</h1>

@if (empty($notifications))

<p class="text-gray-500 text-sm">Nenhuma notificação no momento.</p>

@else

@foreach ($notifications as $n)

<div class="border rounded-2xl px-6 py-5
    {{ $n['type'] === 'budget_exceeded'   ? 'border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-900/20' : '' }}
    {{ $n['type'] === 'budget_warning'    ? 'border-yellow-300 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20' : '' }}
    {{ $n['type'] === 'card_closing'      ? 'border-gray-200 dark:border-gray-800' : '' }}
    {{ $n['type'] === 'expense_disputed'  ? 'border-orange-300 dark:border-orange-800 bg-orange-50 dark:bg-orange-900/20' : '' }}
">

    <div class="flex items-start justify-between gap-4">

        @if ($n['type'] === 'card_closing')
        <div>
            <p class="text-sm font-semibold text-black dark:text-white">
                Fechamento do cartão {{ $n['card']->name }}
            </p>
            <p class="text-xs text-gray-500 mt-1">
                Cartão de {{ $n['owner']->name }} · fecha dia {{ $n['card']->closing_day }}
                ({{ $n['closing_date']->format('d/m/Y') }})
            </p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            @if ($n['days_until'] === 0)
                <span class="text-xs font-semibold text-red-500">Hoje</span>
            @elseif ($n['days_until'] === 1)
                <span class="text-xs font-semibold text-orange-500">Amanhã</span>
            @else
                <span class="text-xs font-semibold text-yellow-600">em {{ $n['days_until'] }} dias</span>
            @endif
            <form method="POST" action="{{ route('notifications.dismiss') }}">
                @csrf
                <input type="hidden" name="keys[]" value="{{ $n['key'] }}">
                <button type="submit" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors" title="Dispensar">✕</button>
            </form>
        </div>

        @elseif ($n['type'] === 'budget_exceeded')
        <div>
            <p class="text-sm font-semibold text-red-600 dark:text-red-400">
                Orçamento estourado — {{ $n['category'] }}
            </p>
            <p class="text-xs text-gray-500 mt-1">
                Gasto: R$ {{ number_format($n['spent'], 2, ',', '.') }}
                · Limite: R$ {{ number_format($n['limit'], 2, ',', '.') }}
                · Excesso: R$ {{ number_format($n['overflow'], 2, ',', '.') }}
            </p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <span class="text-xs font-semibold text-red-500">Estourado</span>
            <form method="POST" action="{{ route('notifications.dismiss') }}">
                @csrf
                <input type="hidden" name="keys[]" value="{{ $n['key'] }}">
                <button type="submit" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors" title="Dispensar">✕</button>
            </form>
        </div>

        @elseif ($n['type'] === 'budget_warning')
        <div>
            <p class="text-sm font-semibold text-yellow-700 dark:text-yellow-400">
                Orçamento quase no limite — {{ $n['category'] }}
            </p>
            <p class="text-xs text-gray-500 mt-1">
                Gasto: R$ {{ number_format($n['spent'], 2, ',', '.') }}
                · Limite: R$ {{ number_format($n['limit'], 2, ',', '.') }}
                · {{ $n['pct'] }}% usado
            </p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <span class="text-xs font-semibold text-yellow-600">{{ $n['pct'] }}%</span>
            <form method="POST" action="{{ route('notifications.dismiss') }}">
                @csrf
                <input type="hidden" name="keys[]" value="{{ $n['key'] }}">
                <button type="submit" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors" title="Dispensar">✕</button>
            </form>
        </div>

        @elseif ($n['type'] === 'expense_disputed')
        <div>
            <p class="text-sm font-semibold text-orange-600 dark:text-orange-400">
                Despesa contestada pelo parceiro(a)
            </p>
            <p class="text-xs text-gray-500 mt-1">
                "{{ $n['expense']->description }}"
                · R$ {{ number_format($n['expense']->amount, 2, ',', '.') }}
                · {{ $n['expense']->expense_date?->format('d/m/Y') }}
            </p>
        </div>
        <a href="{{ route('expenses.index') }}"
            class="text-xs font-semibold text-orange-500 shrink-0">
            Ver →
        </a>
        @endif

    </div>

</div>

@endforeach

@endif

</div>
</div>

</x-app-layout>
