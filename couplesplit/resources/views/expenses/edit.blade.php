<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-xl">
<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl
shadow-sm
p-6 sm:p-10
">

<h1 class="text-3xl font-bold text-black dark:text-white mb-6">
Editar despesa
</h1>

@if ($hasPayments)
<p class="text-sm text-yellow-600 dark:text-yellow-400 mb-6">
Esta despesa já possui pagamentos. Apenas a descrição pode ser alterada.
</p>
@endif

@if ($errors->any())
<div class="bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 p-4 rounded-xl mb-6">
<ul class="list-disc list-inside text-sm">
@foreach ($errors->all() as $error)
<li>{{ $error }}</li>
@endforeach
</ul>
</div>
@endif

<form method="POST" action="{{ route('expenses.update', $expense) }}" class="space-y-6">
@csrf
@method('PUT')

<input
name="description"
value="{{ old('description', $expense->description) }}"
placeholder="Descrição"
class="
w-full px-4 py-3 rounded-xl
border border-gray-300 dark:border-gray-700
bg-white dark:bg-black text-black dark:text-white
focus:outline-none focus:ring-2 focus:ring-black dark:focus:ring-white
"
/>

<textarea
name="notes"
placeholder="Observação (opcional)"
rows="2"
class="
w-full px-4 py-3 rounded-xl
border border-gray-300 dark:border-gray-700
bg-white dark:bg-black text-black dark:text-white
resize-none
">{{ old('notes', $expense->notes) }}</textarea>

@if (!$hasPayments)

<input
name="amount"
type="number"
step="0.01"
value="{{ old('amount', $expense->amount) }}"
placeholder="Valor"
class="
w-full px-4 py-3 rounded-xl
border border-gray-300 dark:border-gray-700
bg-white dark:bg-black text-black dark:text-white
"
/>

<input
name="expense_date"
type="date"
value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}"
class="
w-full px-4 py-3 rounded-xl
border border-gray-300 dark:border-gray-700
bg-white dark:bg-black text-black dark:text-white
"
/>

<select
name="category"
required
class="
w-full px-4 py-3 rounded-xl
border border-gray-300 dark:border-gray-700
bg-white dark:bg-black text-black dark:text-white
">
<option value="" disabled>Selecione uma categoria</option>
@foreach (\App\Models\Expense::CATEGORIES as $cat)
<option value="{{ $cat }}" {{ old('category', $expense->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
@endforeach
@if ($customCats->isNotEmpty())
<optgroup label="Personalizadas">
@foreach ($customCats as $cat)
<option value="{{ $cat }}" {{ old('category', $expense->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
@endforeach
</optgroup>
@endif
</select>

<select
id="card_select"
name="card_id"
class="
w-full px-4 py-3 rounded-xl
border border-gray-300 dark:border-gray-700
bg-white dark:bg-black text-black dark:text-white
">
<option value="" data-type="">Sem cartão (dinheiro / pix)</option>
@foreach ($cards as $card)
<option
value="{{ $card->id }}"
data-type="{{ $card->type }}"
{{ old('card_id', $expense->card_id) == $card->id ? 'selected' : '' }}>
{{ $card->name }} ({{ ucfirst($card->type) }})
</option>
@endforeach
</select>

<input type="hidden" name="is_shared" value="0">
<label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300">
<input
    type="checkbox"
    id="is_shared_check"
    name="is_shared"
    value="1"
    {{ old('is_shared', $expense->is_shared) ? 'checked' : '' }}
    class="rounded border-gray-300"
    onchange="toggleSplitRatio()"
/>
Despesa compartilhada
</label>

@php $currentRatio = old('split_ratio', $splitRatioDisplay); @endphp
<div id="split_ratio_wrapper">
<label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">
    Sua parte: <span id="ratio_display">{{ $currentRatio }}</span>% &nbsp;|&nbsp; Parceiro(a): <span id="ratio_other">{{ 100 - $currentRatio }}</span>%
</label>
<input
    id="split_ratio_input"
    name="split_ratio"
    type="range"
    min="1"
    max="99"
    value="{{ $currentRatio }}"
    oninput="updateRatio(this.value)"
    class="w-full"
/>
</div>

<label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300">
<input
    type="checkbox"
    name="is_recurring"
    value="1"
    {{ old('is_recurring', $expense->is_recurring) ? 'checked' : '' }}
    class="rounded border-gray-300"
/>
Despesa recorrente (repete todo mês)
</label>

<script>
function updateRatio(val) {
    document.getElementById('ratio_display').textContent = val;
    document.getElementById('ratio_other').textContent   = 100 - val;
}
function toggleSplitRatio() {
    var shared = document.getElementById('is_shared_check').checked;
    document.getElementById('split_ratio_wrapper').style.display = shared ? '' : 'none';
}
toggleSplitRatio();
</script>

@endif

<button type="submit" class="
w-full px-6 py-3 rounded-full font-semibold
bg-black text-white dark:bg-white dark:text-black
hover:opacity-90 transition
">
Salvar alterações
</button>

</form>

</div>
</div>
</div>

</x-app-layout>
