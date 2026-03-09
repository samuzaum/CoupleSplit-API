<x-app-layout>

<div class="mt-12 pb-20 px-6">

<div class="mx-auto max-w-3xl space-y-6">
{{-- ======================
FILTROS
====================== --}}
<div class="
border-b border-gray-200 dark:border-gray-800
flex gap-6
text-sm
">

<a href="{{ route('expenses.index') }}"
class="pb-2 border-b-2 font-medium
{{ $context === 'all'
? 'border-black dark:border-white text-black dark:text-white'
: 'border-transparent text-gray-500 hover:text-black dark:hover:text-white' }}">

Todas

</a>

<a href="{{ route('expenses.couple') }}"
class="pb-2 border-b-2 font-medium
{{ $context === 'couple'
? 'border-black dark:border-white text-black dark:text-white'
: 'border-transparent text-gray-500 hover:text-black dark:hover:text-white' }}">

Casal

</a>

<a href="{{ route('expenses.personal') }}"
class="pb-2 border-b-2 font-medium
{{ $context === 'personal'
? 'border-black dark:border-white text-black dark:text-white'
: 'border-transparent text-gray-500 hover:text-black dark:hover:text-white' }}">

Pessoal

</a>

</div>



{{-- ======================
BOTÃO NOVA DESPESA
====================== --}}
<div>

<a href="{{ route('expenses.create') }}"
class="
inline-block
px-5 py-2
rounded-full
bg-black text-white
dark:bg-white dark:text-black
font-semibold
">

Nova despesa

</a>

</div>



{{-- ======================
LISTAGEM
====================== --}}
@if($expenses->isEmpty())

<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl
p-8
text-center
">

<p class="text-gray-500">
Nenhuma despesa registrada ainda
</p>

</div>

@else


<div class="space-y-3">

@foreach($expenses as $expense)

<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-2xl
p-5
flex justify-between items-center
">

<div>

<p class="font-medium text-black dark:text-white">
{{ $expense->description }}
</p>

<p class="text-xs text-gray-500">

{{ $expense->expense_date->format('d/m/Y') }}

• {{ $expense->is_shared ? 'Compartilhada' : 'Pessoal' }}

• {{ $expense->payer->name }}

</p>

</div>


<strong class="text-lg">

R$ {{ number_format($expense->amount,2,',','.') }}

</strong>

</div>

@endforeach

</div>


{{-- ======================
TOTAL
====================== --}}
<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl
p-6
text-right
">

<p class="text-sm text-gray-500">
Total
</p>

<p class="text-2xl font-bold text-black dark:text-white">

R$ {{ number_format($total,2,',','.') }}

</p>

</div>

@endif

</div>

</div>

</x-app-layout>