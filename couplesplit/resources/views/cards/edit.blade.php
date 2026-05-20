<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-xl space-y-6">
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-6 sm:p-10">

<h1 class="text-3xl font-bold text-black dark:text-white mb-6">Editar cartão</h1>

@if ($errors->any())
<div class="bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 p-4 rounded-xl mb-6 text-sm">
    <ul class="list-disc list-inside">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('cards.update', $card) }}" class="space-y-5">
    @csrf
    @method('PUT')

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome</label>
        <input id="name" name="name" value="{{ old('name', $card->name) }}"
            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white"
        />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
        <select id="type" name="type"
            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white">
            <option value="credit" @selected($card->type === 'credit')>Crédito</option>
            <option value="debit"  @selected($card->type === 'debit')>Débito</option>
        </select>
    </div>

    <div id="closing-day-field">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dia de fechamento</label>
        <input id="closing_day" name="closing_day" type="number" min="1" max="31"
            value="{{ old('closing_day', $card->closing_day) }}"
            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white"
        />
    </div>

    <div id="due-day-field">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dia de vencimento</label>
        <input id="due_day" name="due_day" type="number" min="1" max="28"
            value="{{ old('due_day', $card->due_day) }}"
            class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white"
        />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Dia em que a fatura vence (usado para calcular datas de parcelas).</p>
    </div>

    <button type="submit"
        class="w-full px-6 py-3 rounded-full font-semibold bg-black text-white dark:bg-white dark:text-black hover:opacity-90 transition">
        Salvar alterações
    </button>
</form>

</div>

{{-- zona de perigo --}}
<div class="bg-white dark:bg-black border border-red-200 dark:border-red-900 rounded-3xl p-8">
    <h3 class="font-semibold text-red-600 dark:text-red-400 mb-4">Zona de perigo</h3>
    <form method="POST" action="{{ route('cards.destroy', $card) }}"
        onsubmit="return confirm('Tem certeza que quer excluir este cartão?')">
        @csrf
        @method('DELETE')
        <button type="submit"
            class="px-5 py-2 rounded-full border border-red-400 dark:border-red-600 text-red-600 dark:text-red-400 text-sm font-semibold hover:bg-red-50 dark:hover:bg-red-950 transition">
            Excluir cartão
        </button>
    </form>
</div>

</div>
</div>

<script>
const typeSelect   = document.getElementById('type');
const closingField = document.getElementById('closing-day-field');
const dueField     = document.getElementById('due-day-field');
function toggleClosingDay() {
    const isCredit = typeSelect.value === 'credit';
    closingField.style.display = isCredit ? '' : 'none';
    dueField.style.display     = isCredit ? '' : 'none';
}
typeSelect.addEventListener('change', toggleClosingDay);
toggleClosingDay();
</script>

</x-app-layout>
