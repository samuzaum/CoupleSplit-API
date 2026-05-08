<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-2xl space-y-8">

<div class="flex items-center justify-between">
    <a href="{{ route('calendar.index', ['month' => $prevMonth->format('Y-m')]) }}"
        class="px-4 py-2 rounded-full border border-gray-300 dark:border-gray-700 text-sm">
        ← {{ $prevMonth->translatedFormat('M/y') }}
    </a>

    <h1 class="text-2xl font-bold text-black dark:text-white capitalize">
        {{ $currentMonth->translatedFormat('F Y') }}
    </h1>

    <a href="{{ route('calendar.index', ['month' => $nextMonth->format('Y-m')]) }}"
        class="px-4 py-2 rounded-full border border-gray-300 dark:border-gray-700 text-sm">
        {{ $nextMonth->translatedFormat('M/y') }} →
    </a>
</div>

@if ($grouped->isEmpty())

<p class="text-gray-500 text-sm">Nenhum evento registrado.</p>

@else

@foreach ($grouped as $dayKey => $events)

@php
    $date  = \Carbon\Carbon::createFromFormat('Y-m-d', $dayKey);
    $label = ucfirst($date->translatedFormat('l, d \d\e F'));
    $isToday = $date->isToday();
@endphp

<div>

<h2 class="text-sm font-semibold mb-2 flex items-center gap-2
    {{ $isToday ? 'text-black dark:text-white' : 'text-gray-500 dark:text-gray-400' }}">
    {{ $label }}
    @if ($isToday)<span class="text-xs px-2 py-0.5 rounded-full bg-black dark:bg-white text-white dark:text-black">hoje</span>@endif
</h2>

<div class="border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">

@foreach ($events as $event)

<div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-900 last:border-0">

    <div>

        <p class="text-sm font-medium text-black dark:text-white flex items-center gap-2 flex-wrap">
            {{ $event['description'] }}
            @if ($event['label'])
                <span class="text-gray-400 font-normal">({{ $event['label'] }})</span>
            @endif
            @if ($event['is_recurring'])
                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300">↻ recorrente</span>
            @endif
            @if ($event['is_generated'])
                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400">gerada auto</span>
            @endif
        </p>

        <p class="text-xs text-gray-500 mt-0.5">
            {{ $event['date']->format('d/m/Y') }}
            @if ($event['card'])
                · {{ $event['card']->name }}
            @endif
        </p>

    </div>

    <div class="text-right">

        <p class="text-sm font-semibold {{ $event['paid'] ? 'text-green-500' : 'text-black dark:text-white' }}">
            R$ {{ number_format($event['amount'], 2, ',', '.') }}
        </p>

        @if ($event['paid'])
        <p class="text-xs text-green-500">pago</p>
        @endif

    </div>

</div>

@endforeach

</div>

</div>

@endforeach

@endif

</div>
</div>

</x-app-layout>
