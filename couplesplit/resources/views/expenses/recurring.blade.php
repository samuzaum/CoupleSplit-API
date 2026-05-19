<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-2xl space-y-6">

<h1 class="text-3xl font-bold text-black dark:text-white">Despesas recorrentes</h1>

@if (session('success'))
<div class="bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 p-4 rounded-xl text-sm">
    {{ session('success') }}
</div>
@endif

<p class="text-sm text-gray-500">
    Estas despesas são geradas automaticamente no dia 1 de cada mês.
    Cancele a recorrência para parar a geração.
</p>

@if ($templates->isEmpty())
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-8 text-center">
    <p class="text-gray-500 text-sm">Nenhuma despesa recorrente ativa.</p>
    <a href="{{ route('expenses.create') }}"
        class="inline-block mt-4 px-5 py-2 rounded-full bg-black text-white dark:bg-white dark:text-black text-sm font-semibold">
        Nova despesa
    </a>
</div>
@else

<div class="space-y-3">
@foreach ($templates as $expense)
@php
    $ratio   = $expense->split_ratio ?? 0.5;
    $copies  = $expense->children_count;
@endphp

<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl p-5">

    <div class="flex items-start justify-between gap-4">

        <div class="flex-1 min-w-0">
            <p class="font-semibold text-black dark:text-white truncate">{{ $expense->description }}</p>
            <p class="text-xs text-gray-500 mt-1">
                {{ $expense->payer->name }}
                · {{ $expense->category ?? 'Sem categoria' }}
                · {{ $expense->is_shared ? 'Compartilhada (' . round($ratio * 100) . '/' . (100 - round($ratio * 100)) . ')' : 'Pessoal' }}
            </p>
            @if ($expense->notes)
            <p class="text-xs text-gray-400 mt-1 italic">{{ $expense->notes }}</p>
            @endif
            <p class="text-xs text-gray-400 mt-1">
                {{ $copies }} cópia{{ $copies !== 1 ? 's' : '' }} gerada{{ $copies !== 1 ? 's' : '' }} até agora
                · criada em {{ $expense->created_at->format('d/m/Y') }}
            </p>
        </div>

        <div class="flex flex-col items-end gap-2 shrink-0">
            <strong class="text-lg text-black dark:text-white">
                R$ {{ number_format($expense->amount, 2, ',', '.') }}
            </strong>
            <div class="flex items-center gap-3">
                <a href="{{ route('expenses.edit', $expense) }}"
                    class="text-xs text-gray-400 hover:text-black dark:hover:text-white">
                    Editar
                </a>
                <form method="POST" action="{{ route('expenses.stop-recurring', $expense) }}"
                    onsubmit="return confirm('Cancelar a recorrência de \'{{ $expense->description }}\'? As cópias já geradas não são afetadas.')">
                    @csrf
                    <button type="submit" class="text-xs text-red-400 hover:text-red-600">
                        Cancelar recorrência
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>
@endforeach
</div>

@endif

</div>
</div>

</x-app-layout>
