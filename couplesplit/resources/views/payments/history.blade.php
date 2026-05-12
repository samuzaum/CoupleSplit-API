<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-2xl space-y-6">

<div class="flex items-center justify-between flex-wrap gap-3">
<h1 class="text-3xl font-bold text-black dark:text-white">Histórico de pagamentos</h1>
<a href="{{ route('export.payments', array_filter(['month' => request('month')])) }}"
    class="px-4 py-2 rounded-full border border-gray-300 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400">
    ↓ Exportar CSV
</a>
</div>

@if (session('success'))
<div class="bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 p-4 rounded-xl text-sm">{{ session('success') }}</div>
@endif

{{-- filtros --}}
<form method="GET" action="{{ route('payments.history') }}"
    class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl p-5 flex flex-wrap gap-3 items-end">

    <div>
        <input type="month" name="month"
            value="{{ $monthFilter ? $monthFilter->format('Y-m') : '' }}"
            class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white text-sm"
        />
    </div>

    <div>
        <select name="direction"
            class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white text-sm">
            <option value="">Todos</option>
            <option value="sent"     {{ ($directionFilter ?? '') === 'sent'     ? 'selected' : '' }}>Enviados</option>
            <option value="received" {{ ($directionFilter ?? '') === 'received' ? 'selected' : '' }}>Recebidos</option>
        </select>
    </div>

    <button type="submit"
        class="px-5 py-2 rounded-full bg-black text-white dark:bg-white dark:text-black text-sm font-semibold">
        Filtrar
    </button>

    @if (request('month') || request('direction'))
    <a href="{{ route('payments.history') }}"
        class="px-4 py-2 rounded-full border border-gray-300 dark:border-gray-700 text-sm text-gray-500">
        Limpar
    </a>
    @endif
</form>

@if ($payments->isEmpty())
<div class="border border-gray-200 dark:border-gray-800 rounded-3xl p-8 text-center">
<p class="text-gray-500 text-sm">Nenhum pagamento registrado ainda.</p>
</div>
@else

@foreach ($payments as $payment)
@php $isMe = $payment->from_user_id === auth()->id(); @endphp

<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">

    {{-- cabeçalho do pagamento --}}
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-900">
        <div>
            <p class="text-sm font-medium text-black dark:text-white">
                @if ($isMe)
                    Você pagou {{ $payment->toUser->name }}
                @else
                    {{ $payment->fromUser->name }} pagou você
                @endif
            </p>
            <p class="text-xs text-gray-500 mt-0.5">
                {{ $payment->payment_date->format('d/m/Y') }}
                @if ($payment->note) · {{ $payment->note }} @endif
            </p>
        </div>
        <div class="flex items-center gap-3">
            <p class="text-sm font-bold {{ $isMe ? 'text-red-500' : 'text-green-600' }}">
                {{ $isMe ? '-' : '+' }} R$ {{ number_format($payment->amount, 2, ',', '.') }}
            </p>
            @if ($isMe)
            <button type="button" onclick="togglePaymentEdit({{ $payment->id }})"
                class="text-xs text-gray-400 hover:text-black dark:hover:text-white">
                Editar
            </button>
            @endif
        </div>
    </div>

    @if ($isMe)
    <form id="payment_edit_{{ $payment->id }}" method="POST"
        action="{{ route('payments.update', $payment) }}"
        class="hidden px-6 py-4 border-b border-gray-100 dark:border-gray-900 flex gap-3 items-end flex-wrap">
        @csrf
        @method('PATCH')
        <div>
            <label class="block text-xs text-gray-400 mb-1">Data</label>
            <input type="date" name="payment_date"
                value="{{ $payment->payment_date->format('Y-m-d') }}"
                class="px-3 py-1.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white text-sm"
            />
        </div>
        <div class="flex-1 min-w-40">
            <label class="block text-xs text-gray-400 mb-1">Observação</label>
            <input type="text" name="note" value="{{ $payment->note }}" maxlength="255"
                placeholder="Opcional"
                class="w-full px-3 py-1.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white text-sm"
            />
        </div>
        <button type="submit"
            class="px-4 py-1.5 rounded-full bg-black text-white dark:bg-white dark:text-black text-xs font-semibold">
            Salvar
        </button>
        <button type="button" onclick="togglePaymentEdit({{ $payment->id }})"
            class="text-xs text-gray-400">Cancelar</button>
    </form>
    @endif

    {{-- itens quitados --}}
    @if ($payment->items->isNotEmpty())
    <div class="px-6 py-3 space-y-2">
        <p class="text-xs text-gray-400 uppercase tracking-wide mb-2">Dívidas quitadas</p>
        @foreach ($payment->items as $item)
        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
            <span>{{ $item->description ?? 'Despesa' }}</span>
            <span>R$ {{ number_format($item->amount, 2, ',', '.') }}</span>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endforeach

<div class="text-right text-sm text-gray-500">
    {{ $payments->count() }} pagamento{{ $payments->count() !== 1 ? 's' : '' }} no total
</div>

@endif

</div>
</div>

<script>
function togglePaymentEdit(id) {
    var el = document.getElementById('payment_edit_' + id);
    el.classList.toggle('hidden');
    el.classList.toggle('flex');
}
</script>

</x-app-layout>
