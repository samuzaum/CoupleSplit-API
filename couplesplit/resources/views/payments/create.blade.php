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
    O que você quer quitar para
    <strong class="text-black dark:text-white">{{ $partner->name }}</strong>?
</p>

@if ($openDebits->isEmpty())
    <p class="text-sm text-gray-500">Nenhuma dívida em aberto com {{ $partner->name }}! 🎉</p>
@else
    {{-- Dívidas opcionais para selecionar --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Dívidas em aberto — opcional</p>
            <label class="flex items-center gap-1.5 text-xs text-gray-400 cursor-pointer select-none">
                <input type="checkbox" id="select_all" class="rounded border-gray-300" onchange="toggleAll(this)">
                Selecionar tudo
            </label>
        </div>

        <div class="space-y-2">
            @foreach ($openDebits as $debit)
            <label class="debit-item flex items-center justify-between gap-3 p-4 rounded-2xl border border-gray-200 dark:border-gray-800 cursor-pointer transition hover:border-black dark:hover:border-white has-[:checked]:border-black dark:has-[:checked]:border-white has-[:checked]:bg-gray-50 dark:has-[:checked]:bg-gray-900">
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                        class="debit-check rounded border-gray-300 shrink-0"
                        data-amount="{{ $debit->remaining }}"
                        onchange="recalcTotal()">
                    <p class="text-sm font-medium text-black dark:text-white">{{ $debit->label }}</p>
                </div>
                <span class="text-sm font-semibold text-black dark:text-white shrink-0">
                    R$ {{ number_format($debit->remaining, 2, ',', '.') }}
                </span>
            </label>
            @endforeach
        </div>
    </div>
@endif

{{-- Valor — editável, preenchido pelos checks ou manualmente --}}
<div class="space-y-1">
    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
        Valor a pagar
        @if ($openDebits->isNotEmpty())
            <span class="text-xs text-gray-400 font-normal">(preenchido pelos itens ou digite livremente)</span>
        @endif
    </label>
    <input type="number" name="amount" id="amount_input" step="0.01" min="0.01" required
        placeholder="R$ 0,00"
        class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white px-4 py-3 outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
</div>

<button type="submit"
    class="w-full py-3 rounded-full font-semibold bg-black text-white dark:bg-white dark:text-black hover:opacity-90 transition">
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

function recalcTotal() {
    var checks     = document.querySelectorAll('.debit-check');
    var total      = 0;
    var anyChecked = false;

    checks.forEach(function(c) {
        if (c.checked) {
            total += parseFloat(c.dataset.amount);
            anyChecked = true;
        }
    });

    // Preenche o campo de valor com o total dos checks
    if (anyChecked) {
        document.getElementById('amount_input').value = total.toFixed(2);
    } else {
        document.getElementById('amount_input').value = '';
    }

    // Sincroniza "selecionar tudo"
    var selectAll = document.getElementById('select_all');
    if (selectAll) {
        selectAll.checked      = anyChecked && [...checks].every(c => c.checked);
        selectAll.indeterminate = anyChecked && ![...checks].every(c => c.checked);
    }
}

function toggleAll(master) {
    document.querySelectorAll('.debit-check').forEach(function(c) {
        c.checked = master.checked;
    });
    recalcTotal();
}

setType('partner');
</script>

</x-app-layout>
