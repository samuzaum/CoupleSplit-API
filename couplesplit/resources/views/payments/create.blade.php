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
<input type="hidden" name="amount" id="partner_amount" value="0">

<p class="text-sm text-gray-500">
    O que você quer quitar para
    <strong class="text-black dark:text-white">{{ $partner->name }}</strong>?
</p>

@if ($openDebits->isEmpty())
    <p class="text-sm text-gray-500">Nenhuma dívida em aberto com {{ $partner->name }}! 🎉</p>
@else
    {{-- Selecionar tudo --}}
    <label class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 cursor-pointer select-none">
        <input type="checkbox" id="select_all" class="rounded border-gray-300" onchange="toggleAll(this)">
        Selecionar tudo
    </label>

    {{-- Lista de dívidas --}}
    <div class="space-y-3" id="debits_list">
        @foreach ($openDebits as $debit)
        <label class="debit-item flex items-center justify-between gap-3 p-4 rounded-2xl border border-gray-200 dark:border-gray-800 cursor-pointer transition hover:border-black dark:hover:border-white has-[:checked]:border-black dark:has-[:checked]:border-white has-[:checked]:bg-gray-50 dark:has-[:checked]:bg-gray-900">
            <div class="flex items-center gap-3">
                <input type="checkbox"
                    class="debit-check rounded border-gray-300 shrink-0"
                    data-amount="{{ $debit->remaining }}"
                    onchange="recalcTotal()">
                <div>
                    <p class="text-sm font-medium text-black dark:text-white">{{ $debit->label }}</p>
                    <p class="text-xs text-gray-400">{{ $debit->origin === 'expense' ? 'Despesa' : 'Outro' }}</p>
                </div>
            </div>
            <span class="text-sm font-semibold text-black dark:text-white shrink-0">
                R$ {{ number_format($debit->remaining, 2, ',', '.') }}
            </span>
        </label>
        @endforeach
    </div>

    {{-- Rodapé com total --}}
    <div class="border-t border-gray-200 dark:border-gray-800 pt-4 flex justify-between items-center">
        <span class="text-sm text-gray-500">Total selecionado</span>
        <span id="total_display" class="text-xl font-bold text-black dark:text-white">R$ 0,00</span>
    </div>

    <button id="btn_confirmar" type="submit" disabled
        class="w-full py-3 rounded-full font-semibold transition
               bg-gray-200 text-gray-400 dark:bg-gray-800 dark:text-gray-600
               disabled:cursor-not-allowed">
        Selecione ao menos uma dívida
    </button>
@endif
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
    var checks  = document.querySelectorAll('.debit-check');
    var total   = 0;
    var anyChecked = false;

    checks.forEach(function(c) {
        if (c.checked) {
            total += parseFloat(c.dataset.amount);
            anyChecked = true;
        }
    });

    // Atualiza display
    document.getElementById('total_display').textContent =
        'R$ ' + total.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    // Atualiza campo hidden
    document.getElementById('partner_amount').value = total.toFixed(2);

    // Habilita/desabilita botão
    var btn = document.getElementById('btn_confirmar');
    if (anyChecked) {
        btn.disabled = false;
        btn.className = 'w-full py-3 rounded-full font-semibold transition bg-black text-white dark:bg-white dark:text-black hover:opacity-90';
        btn.textContent = 'Confirmar pagamento';
    } else {
        btn.disabled = true;
        btn.className = 'w-full py-3 rounded-full font-semibold transition bg-gray-200 text-gray-400 dark:bg-gray-800 dark:text-gray-600 disabled:cursor-not-allowed';
        btn.textContent = 'Selecione ao menos uma dívida';
    }

    // Sincroniza "selecionar tudo"
    var selectAll = document.getElementById('select_all');
    if (selectAll) {
        selectAll.checked = anyChecked && [...checks].every(c => c.checked);
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
