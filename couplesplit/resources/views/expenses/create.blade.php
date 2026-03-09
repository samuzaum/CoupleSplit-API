<x-app-layout>

<div class="mt-12 pb-20 px-6">

<div class="mx-auto max-w-xl">

<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl
shadow-sm
p-10
transition-colors
">

<h1 class="
text-3xl font-bold
text-black dark:text-white
mb-6
">
Nova despesa
</h1>


@if ($errors->any())

<div class="
bg-red-100 dark:bg-red-900/40
text-red-700 dark:text-red-300
p-4
rounded-xl
mb-6
">

<ul class="list-disc list-inside text-sm">

@foreach ($errors->all() as $error)

<li>{{ $error }}</li>

@endforeach

</ul>

</div>

@endif


<form method="POST" action="{{ route('expenses.store') }}" class="space-y-6">

@csrf


{{-- descrição --}}
<input
name="description"
value="{{ old('description') }}"
placeholder="Descrição"

class="
w-full
px-4 py-3
rounded-xl
border border-gray-300 dark:border-gray-700
bg-white dark:bg-black
text-black dark:text-white
focus:outline-none
focus:ring-2
focus:ring-black dark:focus:ring-white
"
/>


{{-- valor --}}
<input
name="amount"
type="number"
step="0.01"
value="{{ old('amount') }}"
placeholder="Valor"

class="
w-full
px-4 py-3
rounded-xl
border border-gray-300 dark:border-gray-700
bg-white dark:bg-black
text-black dark:text-white
"
/>


{{-- data --}}
<input
name="expense_date"
type="date"
value="{{ old('expense_date') }}"

class="
w-full
px-4 py-3
rounded-xl
border border-gray-300 dark:border-gray-700
bg-white dark:bg-black
text-black dark:text-white
"
/>


{{-- cartão --}}
<select
name="card_id"

class="
w-full
px-4 py-3
rounded-xl
border border-gray-300 dark:border-gray-700
bg-white dark:bg-black
text-black dark:text-white
">

<option value="">
Sem cartão (dinheiro / pix)
</option>

@foreach ($cards as $card)

<option value="{{ $card->id }}">

{{ $card->name }} ({{ ucfirst($card->type) }})

</option>

@endforeach

</select>


<input type="hidden" name="is_shared" value="0">


<label class="
flex items-center gap-3
text-sm
text-gray-700 dark:text-gray-300
">

<input
type="checkbox"
name="is_shared"
value="1"
{{ old('is_shared', true) ? 'checked' : '' }}

class="rounded border-gray-300"
/>

Despesa compartilhada

</label>


<button
type="submit"

class="
w-full
px-6 py-3
rounded-full
font-semibold
bg-black text-white
dark:bg-white dark:text-black
hover:opacity-90
transition
"
>

Salvar despesa

</button>

</form>

</div>

</div>

</div>

</x-app-layout>