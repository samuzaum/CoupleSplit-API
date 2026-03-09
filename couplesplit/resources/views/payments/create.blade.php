<x-app-layout>

<div class="mt-12 pb-20 px-6">

<div class="mx-auto max-w-xl space-y-6">

<h1 class="text-3xl font-bold text-black dark:text-white">
Registrar pagamento
</h1>

<form
method="POST"
action="{{ route('payments.store') }}"
class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl
p-8
space-y-6
"
>

@csrf

<p class="text-sm text-gray-500">
Pagamento entre você e
<strong class="text-black dark:text-white">
{{ $partner->name }}
</strong>
</p>



{{-- VALOR --}}
<div class="space-y-1">

<label class="text-sm font-medium text-gray-700 dark:text-gray-300">
Valor do pagamento
</label>

<input
type="number"
name="amount"
step="0.01"
min="0.01"
required
class="
w-full
rounded-xl
border border-gray-300
dark:border-gray-700
bg-white dark:bg-black
px-4 py-2
focus:ring-2 focus:ring-black
dark:focus:ring-white
outline-none
"
>

</div>



{{-- DÉBITOS EM ABERTO --}}
<div class="
bg-gray-50 dark:bg-gray-900
border border-gray-200 dark:border-gray-800
rounded-2xl
p-5
space-y-3
">

<p class="font-semibold text-black dark:text-white">
Dívidas em aberto
</p>

@if ($openDebits->isEmpty())

<p class="text-sm text-gray-500">
Nenhuma dívida em aberto!
</p>

@else

<div class="space-y-2">

@foreach ($openDebits as $debit)

<div class="flex justify-between text-sm">

<span class="text-gray-700 dark:text-gray-300">
{{ $debit->description ?? 'Despesa' }}
</span>

<strong>
R$ {{ number_format($debit->amount - $debit->used_amount, 2, ',', '.') }}
</strong>

</div>

@endforeach

</div>


<div class="
border-t border-gray-200 dark:border-gray-800
pt-3
flex justify-between
text-sm
">

<span class="text-gray-600">
Total em aberto
</span>

<strong class="text-black dark:text-white">

R$
{{ number_format(
$openDebits->sum(fn($d) => $d->amount - $d->used_amount),
2, ',', '.'
) }}

</strong>

</div>

@endif

</div>



{{-- AVISO --}}
<div class="
bg-blue-50 dark:bg-blue-900/30
border border-blue-200 dark:border-blue-900
rounded-2xl
p-4
text-sm
text-blue-800 dark:text-blue-200
">

O valor pago será automaticamente abatido das dívidas mais antigas.
Caso o valor seja maior, o saldo vira crédito.

</div>



{{-- BOTÃO --}}
<div class="pt-2">

<button
class="
w-full
py-3
rounded-full
font-semibold
bg-black text-white
dark:bg-white dark:text-black
hover:opacity-90
transition
"
>

Confirmar pagamento

</button>

</div>

</form>

</div>

</div>

</x-app-layout>