<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-2xl space-y-6">

<h1 class="text-3xl font-bold text-black dark:text-white">Metas de economia</h1>

@if (session('success'))
<div class="bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 p-4 rounded-xl text-sm">
    {{ session('success') }}
</div>
@endif

{{-- formulário nova meta --}}
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-8">
<h3 class="font-semibold text-black dark:text-white mb-4">Nova meta</h3>
<form method="POST" action="{{ route('goals.store') }}" class="space-y-4">
@csrf

@if ($errors->any())
<div class="bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 p-3 rounded-xl text-sm">
    <ul class="list-disc list-inside">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
    </ul>
</div>
@endif

<input name="name" value="{{ old('name') }}" placeholder="Nome da meta (ex: Viagem, Reserva de emergência)"
    required maxlength="100"
    class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white"
/>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <label class="block text-xs text-gray-400 mb-1">Valor alvo (R$)</label>
        <input name="target_amount" type="number" step="0.01" min="1"
            value="{{ old('target_amount') }}" placeholder="Ex: 5000" required
            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white"
        />
    </div>
    <div>
        <label class="block text-xs text-gray-400 mb-1">Data alvo (opcional)</label>
        <input name="target_date" type="date" value="{{ old('target_date') }}"
            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white"
        />
    </div>
</div>

<div class="flex items-center gap-3">
    <label class="text-xs text-gray-400">Cor</label>
    <input name="color" type="color" value="{{ old('color', '#000000') }}"
        class="w-10 h-8 rounded border border-gray-300 dark:border-gray-700 cursor-pointer"
    />
</div>

<button type="submit"
    class="w-full py-3 rounded-full font-semibold bg-black text-white dark:bg-white dark:text-black hover:opacity-90 transition">
    Criar meta
</button>
</form>
</div>

{{-- listagem de metas --}}
@if ($goals->isEmpty())
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-8 text-center">
    <p class="text-gray-500 text-sm">Nenhuma meta criada ainda.</p>
</div>
@else

<div class="space-y-4">
@foreach ($goals as $goal)
@php
$pct        = $goal->progress;
$remaining  = $goal->remaining;
$isDone     = $pct >= 100;
$barColor   = $isDone ? 'bg-green-500' : 'bg-black dark:bg-white';
@endphp

<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl p-6 space-y-4">

    <div class="flex items-start justify-between">
        <div class="flex items-center gap-3">
            <span class="w-3 h-3 rounded-full shrink-0" style="background:{{ $goal->color }}"></span>
            <div>
                <p class="font-semibold text-black dark:text-white">{{ $goal->name }}</p>
                @if ($goal->target_date)
                <p class="text-xs text-gray-400">
                    Alvo: {{ $goal->target_date->format('d/m/Y') }}
                    @php $daysLeft = now()->diffInDays($goal->target_date, false); @endphp
                    @if ($daysLeft > 0)
                        · {{ $daysLeft }} dias restantes
                    @elseif (!$isDone)
                        · <span class="text-red-500">Prazo vencido</span>
                    @endif
                </p>
                @endif
            </div>
        </div>
        <form method="POST" action="{{ route('goals.destroy', $goal) }}"
            onsubmit="return confirm('Remover meta \'{{ $goal->name }}\'?')">
            @csrf @method('DELETE')
            <button type="submit" class="text-xs text-red-400 hover:text-red-600">Remover</button>
        </form>
    </div>

    {{-- barra de progresso --}}
    <div>
        <div class="flex justify-between text-sm mb-1">
            <span class="text-gray-500">
                R$ {{ number_format($goal->current_amount, 2, ',', '.') }}
                de R$ {{ number_format($goal->target_amount, 2, ',', '.') }}
            </span>
            <span class="font-semibold {{ $isDone ? 'text-green-600' : 'text-black dark:text-white' }}">
                {{ $pct }}%
                @if ($isDone) ✓ @endif
            </span>
        </div>
        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2.5">
            <div class="{{ $barColor }} h-2.5 rounded-full transition-all" style="width:{{ $pct }}%"></div>
        </div>
        @if (!$isDone)
        <p class="text-xs text-gray-400 mt-1">Faltam R$ {{ number_format($remaining, 2, ',', '.') }}</p>
        @endif
    </div>

    {{-- contribuição --}}
    @if (!$isDone)
    <form method="POST" action="{{ route('goals.contribute', $goal) }}"
        class="flex gap-2 items-center">
        @csrf
        <input type="number" name="amount" step="0.01" min="0.01" placeholder="Valor a adicionar (R$)"
            class="flex-1 px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white text-sm"
        />
        <button type="submit"
            class="px-4 py-2 rounded-full bg-black text-white dark:bg-white dark:text-black text-sm font-semibold">
            + Contribuir
        </button>
    </form>
    @else
    <p class="text-sm text-green-600 font-semibold text-center">🎉 Meta atingida!</p>
    @endif

</div>
@endforeach
</div>

@endif

</div>
</div>

</x-app-layout>
