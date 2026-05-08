<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-2xl space-y-4">

<h1 class="text-3xl font-bold text-black dark:text-white">Log de atividades</h1>

@if ($logs->isEmpty())
<div class="border border-gray-200 dark:border-gray-800 rounded-3xl p-8 text-center">
    <p class="text-gray-500 text-sm">Nenhuma atividade registrada ainda.</p>
</div>
@else

@php
$icons = [
    'created' => '＋',
    'updated' => '✎',
    'deleted' => '✕',
    'payment' => '↑',
];
$colors = [
    'created' => 'text-green-600',
    'updated' => 'text-blue-500',
    'deleted' => 'text-red-500',
    'payment' => 'text-purple-500',
];
@endphp

<div class="relative">
    {{-- linha vertical --}}
    <div class="absolute left-4 top-0 bottom-0 w-px bg-gray-100 dark:bg-gray-800"></div>

    <ul class="space-y-4 pl-12">
    @foreach ($logs as $log)
    @php
        $icon  = $icons[$log->action]  ?? '·';
        $color = $colors[$log->action] ?? 'text-gray-400';
    @endphp
    <li class="relative">
        {{-- marcador --}}
        <span class="absolute -left-9 top-1 w-6 h-6 rounded-full bg-white dark:bg-black border border-gray-200 dark:border-gray-800
            flex items-center justify-center text-xs font-bold {{ $color }}">
            {{ $icon }}
        </span>

        <div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl px-5 py-4">
            <p class="text-sm text-black dark:text-white">{{ $log->description }}</p>
            <p class="text-xs text-gray-400 mt-1">
                {{ $log->user->name }} · {{ $log->created_at->diffForHumans() }}
            </p>
        </div>
    </li>
    @endforeach
    </ul>
</div>

<p class="text-xs text-gray-400 text-center">Mostrando as últimas {{ $logs->count() }} atividades</p>
@endif

</div>
</div>

</x-app-layout>
