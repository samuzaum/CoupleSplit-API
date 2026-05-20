<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-xl">
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-6 sm:p-10">

<h1 class="text-3xl font-bold text-black dark:text-white mb-6">Novo cartão</h1>

@if ($errors->any())
<div class="bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 p-4 rounded-xl mb-6 text-sm">
    <ul class="list-disc list-inside">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('cards.store') }}" class="space-y-5">
    @csrf

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome</label>
        <input id="name" name="name" value="{{ old('name') }}" placeholder="Ex: Nubank, Itaú..."
            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white"
        />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
        <select id="type" name="type"
            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white">
            <option value="credit" @selected(old('type') === 'credit')>Crédito</option>
            <option value="debit"  @selected(old('type') === 'debit')>Débito</option>
        </select>
    </div>

    <div id="closing-day-field">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dia de fechamento</label>
        <input id="closing_day" name="closing_day" type="number" min="1" max="31"
            value="{{ old('closing_day') }}" placeholder="Ex: 15"
            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white"
        />
    </div>

    <div id="due-day-field">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dia de vencimento</label>
        <input id="due_day" name="due_day" type="number" min="1" max="28"
            value="{{ old('due_day') }}" placeholder="Ex: 10"
            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white"
        />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Dia em que a fatura vence (usado para calcular datas de parcelas).</p>
    </div>

    <button type="submit"
        class="w-full px-6 py-3 rounded-full font-semibold bg-black text-white dark:bg-white dark:text-black hover:opacity-90 transition">
        Salvar cartão
    </button>
</form>

</div>
</div>
</div>

<script>
const typeSelect    = document.getElementById('type');
const closingField  = document.getElementById('closing-day-field');
const dueField      = document.getElementById('due-day-field');
function toggleClosingDay() {
    const isCredit = typeSelect.value === 'credit';
    closingField.style.display = isCredit ? '' : 'none';
    dueField.style.display     = isCredit ? '' : 'none';
}
typeSelect.addEventListener('change', toggleClosingDay);
toggleClosingDay();
</script>

</x-app-layout>
