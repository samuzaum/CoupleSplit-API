<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-2xl space-y-6">

<div class="flex items-center justify-between">
    <h1 class="text-3xl font-bold text-black dark:text-white">Meus cartões</h1>
    <a href="{{ route('cards.create') }}"
        class="px-5 py-2 rounded-full bg-black text-white dark:bg-white dark:text-black font-semibold text-sm">
        + Novo
    </a>
</div>

@if (session('success'))
<div class="bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 p-4 rounded-xl text-sm">
    {{ session('success') }}
</div>
@endif

@forelse($cards as $card)
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl px-6 py-5 flex items-center justify-between gap-4">
    <div>
        <p class="font-semibold text-black dark:text-white">{{ $card->name }}</p>
        <p class="text-xs text-gray-500 mt-0.5">
            {{ $card->type === 'credit' ? 'Crédito' : 'Débito' }}
            @if($card->closing_day && $card->type === 'credit')
                · Fecha dia {{ $card->closing_day }}
            @endif
        </p>
    </div>
    <a href="{{ route('cards.edit', $card) }}"
        class="text-sm text-gray-400 hover:text-black dark:hover:text-white transition-colors">
        Editar
    </a>
</div>
@empty
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-8 text-center">
    <p class="text-gray-500 text-sm">Nenhum cartão cadastrado ainda.</p>
</div>
@endforelse

</div>
</div>

</x-app-layout>
