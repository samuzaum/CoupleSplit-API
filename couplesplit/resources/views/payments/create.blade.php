<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-xl space-y-6">

<h1 class="text-3xl font-bold text-black dark:text-white">
Registrar pagamento
</h1>

{{-- SELETOR DE TIPO --}}
<div class="flex gap-3">
<button type="button" id="btn-partner"
    onclick="setType('partner')"
    class="flex-1 py-3 rounded-full font-semibold border text-sm transition">
    Para o(a) parceiro(a)
</button>
<button type="button" id="btn-personal"
    onclick="setType('personal')"
    class="flex-1 py-3 rounded-full font-semibold border text-sm transition">
    Pessoal
</button>
</div>


{{-- FORMULÁRIO PARCEIRO --}}
<form id="form-partner" method="POST" action="{{ route('payments.store') }}"
class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-8 space-y-6">
@csrf
<input type="hidden" name="payment_type" value="partner">

<p class="text-sm text-gray-500">
Pagamento entre você e
<strong class="text-black dark:text-white">{{ $partner->name }}</strong>
</p>

<div class="space-y-1">
<label class="text-sm font-medium text-gray-700 dark:text-gray-300">Valor do pagamento</label>
<input type="number" name="amount" step="0.01" min="0.01" required
    class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black px-4 py-2 outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
</div>

<div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 space-y-3">
<p class="font-semibold text-black dark:text-white">Dívidas em aberto</p>

@if ($openDebits->isEmpty())
<p class="text-sm text-gray-500">Nenhuma dívida em aberto!</p>
@else
<div class="space-y-2">
@foreach ($openDebits as $debit)
<div class="flex justify-between text-sm">
    <span class="text-gray-700 dark:text-gray-300">{{ $debit->description ?? 'Despesa' }}</span>
    <strong>R$ {{ number_format($debit->amount - $debit->used_amount, 2, ',', '.') }}</strong>
</div>
@endforeach
</div>
<div class="border-t border-gray-200 dark:border-gray-800 pt-3 flex justify-between text-sm">
    <span class="text-gray-600">Total em aberto</span>
    <strong>R$ {{ number_format($openDebits->sum(fn($d) => $d->amount - $d->used_amount), 2, ',', '.') }}</strong>
</div>
@endif
</div>

<div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-900 rounded-2xl p-4 text-sm text-blue-800 dark:text-blue-200">
O valor pago será abatido das dívidas mais antigas. Se maior, o saldo vira crédito.
</div>

<button class="w-full py-3 rounded-full font-semibold bg-black text-white dark:bg-white dark:text-black hover:opacity-90 transition">
Confirmar pagamento
</button>
</form>


{{-- FORMULÁRIO PESSOAL --}}
<form id="form-personal" method="POST" action="{{ route('payments.store') }}"
class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-8 space-y-6 hidden">
@csrf
<input type="hidden" name="payment_type" value="personal">

<p class="text-sm text-gray-500">Selecione as despesas pessoais que você pagou:</p>

@if ($personalUnpaid->isEmpty())
<p class="text-sm text-gray-500">Nenhuma despesa pessoal pendente.</p>
@else
<div class="space-y-3">
@foreach ($personalUnpaid as $expense)
<label class="flex items-center justify-between gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-800 cursor-pointer">
    <div class="flex items-center gap-3">
        <input type="checkbox" name="expense_ids[]" value="{{ $expense->id }}"
            class="rounded border-gray-300">
        <div>
            <p class="text-sm font-medium text-black dark:text-white">{{ $expense->description }}</p>
            <p class="text-xs text-gray-500">{{ $expense->expense_date->format('d/m/Y') }}{{ $expense->category ? ' • ' . $expense->category : '' }}</p>
        </div>
    </div>
    <strong class="text-sm">R$ {{ number_format($expense->amount, 2, ',', '.') }}</strong>
</label>
@endforeach
</div>
@endif

<button class="w-full py-3 rounded-full font-semibold bg-black text-white dark:bg-white dark:text-black hover:opacity-90 transition">
Marcar como pagas
</button>
</form>

</div>
</div>

<script>
function setType(type) {
    var isPartner = type === 'partner';
    document.getElementById('form-partner').classList.toggle('hidden', !isPartner);
    document.getElementById('form-personal').classList.toggle('hidden', isPartner);

    document.getElementById('btn-partner').className = 'flex-1 py-3 rounded-full font-semibold border text-sm transition '
        + (isPartner ? 'bg-black text-white dark:bg-white dark:text-black border-black dark:border-white'
                     : 'text-gray-500 border-gray-300 dark:border-gray-700');
    document.getElementById('btn-personal').className = 'flex-1 py-3 rounded-full font-semibold border text-sm transition '
        + (!isPartner ? 'bg-black text-white dark:bg-white dark:text-black border-black dark:border-white'
                      : 'text-gray-500 border-gray-300 dark:border-gray-700');
}

setType('partner');
</script>

</x-app-layout>
