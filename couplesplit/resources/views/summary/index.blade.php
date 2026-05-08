<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-3xl space-y-6">

{{-- navegação de mês --}}
<div class="flex items-center justify-between">
    <a href="{{ route('summary.index', ['month' => $prevMonth->format('Y-m')]) }}"
        class="px-4 py-2 rounded-full border border-gray-300 dark:border-gray-700 text-sm">
        ← {{ $prevMonth->translatedFormat('M/Y') }}
    </a>

    <h1 class="text-2xl font-bold text-black dark:text-white capitalize">
        {{ $month->translatedFormat('F Y') }}
    </h1>

    @if (!$isCurrentMonth)
    <a href="{{ route('summary.index', ['month' => $nextMonth->format('Y-m')]) }}"
        class="px-4 py-2 rounded-full border border-gray-300 dark:border-gray-700 text-sm">
        {{ $nextMonth->translatedFormat('M/Y') }} →
    </a>
    @else
    <div class="w-28"></div>
    @endif
</div>


{{-- totais --}}
<div class="grid grid-cols-3 gap-4">
    <div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl p-5 text-center">
        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total conjunto</p>
        <p class="text-xl font-bold text-black dark:text-white">
            R$ {{ number_format($shared->sum('amount'), 2, ',', '.') }}
        </p>
        <p class="text-xs text-gray-500 mt-1">{{ $shared->count() }} despesa{{ $shared->count() !== 1 ? 's' : '' }}</p>
    </div>
    <div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl p-5 text-center">
        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Pessoal (você)</p>
        <p class="text-xl font-bold text-black dark:text-white">
            R$ {{ number_format($personal->where('paid_by', auth()->id())->sum('amount'), 2, ',', '.') }}
        </p>
        <p class="text-xs text-gray-500 mt-1">{{ $personal->where('paid_by', auth()->id())->count() }} despesa{{ $personal->where('paid_by', auth()->id())->count() !== 1 ? 's' : '' }}</p>
    </div>
    <div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl p-5 text-center">
        <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Pagamentos</p>
        <p class="text-xl font-bold text-black dark:text-white">
            R$ {{ number_format($payments->sum('amount'), 2, ',', '.') }}
        </p>
        <p class="text-xs text-gray-500 mt-1">{{ $payments->count() }} transação{{ $payments->count() !== 1 ? 'ões' : '' }}</p>
    </div>
</div>


{{-- por categoria --}}
@if ($byCategory->isNotEmpty())
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
    <h3 class="font-semibold mb-4 text-black dark:text-white">Por categoria</h3>
    @php $maxCat = $byCategory->max(); @endphp
    <div class="space-y-3">
    @foreach ($byCategory as $cat => $total)
    <div>
        <div class="flex justify-between text-sm mb-1">
            <span class="text-gray-700 dark:text-gray-300">{{ $cat }}</span>
            <span class="font-medium text-black dark:text-white">R$ {{ number_format($total, 2, ',', '.') }}</span>
        </div>
        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5">
            <div class="bg-black dark:bg-white h-1.5 rounded-full" style="width: {{ $maxCat > 0 ? round($total / $maxCat * 100) : 0 }}%"></div>
        </div>
    </div>
    @endforeach
    </div>
</div>
@endif


{{-- quem pagou mais --}}
@if ($byPayer->isNotEmpty())
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
    <h3 class="font-semibold mb-4 text-black dark:text-white">Quem pagou as despesas</h3>
    <div class="space-y-2">
    @foreach ($byPayer as $payer)
    <div class="flex justify-between text-sm">
        <span class="text-gray-700 dark:text-gray-300">{{ $payer['name'] }} ({{ $payer['count'] }} despesa{{ $payer['count'] !== 1 ? 's' : '' }})</span>
        <span class="font-medium text-black dark:text-white">R$ {{ number_format($payer['total'], 2, ',', '.') }}</span>
    </div>
    @endforeach
    </div>
</div>
@endif


{{-- pagamentos do mês --}}
@if ($payments->isNotEmpty())
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
    <h3 class="font-semibold mb-4 text-black dark:text-white">Acertos do mês</h3>
    <div class="space-y-2">
    @foreach ($payments as $payment)
    @php $isMe = $payment->from_user_id === auth()->id(); @endphp
    <div class="flex justify-between text-sm">
        <span class="text-gray-700 dark:text-gray-300">
            {{ $isMe ? 'Você → ' . $payment->toUser->name : $payment->fromUser->name . ' → você' }}
            <span class="text-xs text-gray-400">{{ $payment->payment_date->format('d/m') }}</span>
        </span>
        <span class="font-medium {{ $isMe ? 'text-red-500' : 'text-green-600' }}">
            {{ $isMe ? '-' : '+' }} R$ {{ number_format($payment->amount, 2, ',', '.') }}
        </span>
    </div>
    @endforeach
    </div>
</div>
@endif


{{-- sem dados --}}
@if ($shared->isEmpty() && $personal->isEmpty())
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-10 text-center">
    <p class="text-gray-500 text-sm">Nenhuma despesa registrada neste mês.</p>
</div>
@endif

</div>
</div>

</x-app-layout>
