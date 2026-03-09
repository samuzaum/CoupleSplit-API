<x-app-layout>

<div class="mt-12 pb-20 px-6">

<div class="mx-auto max-w-4xl space-y-6">

@php
$user = auth()->user();
$couple = $user->couples()->first();
@endphp



{{-- ======================
SALDO PRINCIPAL
====================== --}}
<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl
p-10
text-center
">

<p class="text-sm text-gray-500 mb-2">
Saldo entre vocês
</p>

@if ($netBalance > 0)

<p class="text-5xl font-bold text-green-600 mb-2">
+ R$ {{ number_format($netBalance,2,',','.') }}
</p>

<p class="text-sm text-gray-500">
{{ $partner->name }} te deve
</p>

@elseif ($netBalance < 0)

<p class="text-5xl font-bold text-red-600 mb-2">
- R$ {{ number_format(abs($netBalance),2,',','.') }}
</p>

<p class="text-sm text-gray-500">
Você deve {{ $partner->name }}
</p>

@else

<p class="text-3xl font-semibold text-gray-500">
Tudo certo entre vocês
</p>

@endif


<div class="flex justify-center gap-3 mt-6">

<a href="{{ route('payments.create') }}"
class="
px-5 py-2
rounded-full
bg-black text-white
dark:bg-white dark:text-black
font-semibold
">

Registrar pagamento

</a>

<a href="{{ route('expenses.create') }}"
class="
px-5 py-2
rounded-full
border border-gray-300 dark:border-gray-700
">

Nova despesa

</a>

</div>

</div>



{{-- ======================
CASAL
====================== --}}
@if($couple)

<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl
p-8
">

<h3 class="font-semibold mb-3 text-black dark:text-white">
{{ $couple->name ?? 'Seu casal' }}
</h3>

<div class="flex gap-2">

@foreach($couple->users as $member)

<div class="
px-3 py-1
rounded-full
text-sm
bg-gray-100 dark:bg-gray-900
">

{{ $member->name }}

</div>

@endforeach

</div>

</div>

@endif



{{-- ======================
DESPESAS RECENTES
====================== --}}
<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl
p-8
">

<h3 class="font-semibold mb-4 text-black dark:text-white">
Despesas recentes
</h3>

@if($recentExpenses->isEmpty())

<p class="text-sm text-gray-500">
Nenhuma despesa registrada
</p>

@else

<ul class="space-y-3 text-sm">

@foreach($recentExpenses as $expense)

<li class="flex justify-between">

<div>

<p class="text-black dark:text-white">
{{ $expense->description }}
</p>

<p class="text-xs text-gray-500">
{{ $expense->created_at->format('d/m/Y') }}
</p>

</div>

<strong>

R$ {{ number_format($expense->amount,2,',','.') }}

</strong>

</li>

@endforeach

</ul>

@endif

</div>



{{-- ======================
DÉBITOS
====================== --}}
<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl
p-8
">

<h3 class="font-semibold mb-4 text-black dark:text-white">
Dívidas em aberto
</h3>

@if ($openDebits->isEmpty())

<p class="text-sm text-gray-500">
Nenhuma dívida pendente
</p>

@else

<ul class="space-y-3 text-sm">

@foreach ($openDebits as $debit)

@php
$remaining = $debit->amount - $debit->used_amount;
@endphp

<li class="flex justify-between">

<span>
{{ $debit->description }}
</span>

<strong>

R$ {{ number_format($remaining,2,',','.') }}

</strong>

</li>

@endforeach

</ul>

@endif

</div>



{{-- ======================
CRÉDITOS
====================== --}}
<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl
p-8
">

<h3 class="font-semibold mb-4 text-black dark:text-white">
Créditos a receber
</h3>

@if ($openCredits->isEmpty())

<p class="text-sm text-gray-500">
Nenhum crédito pendente
</p>

@else

<ul class="space-y-3 text-sm">

@foreach ($openCredits as $credit)

@php
$remaining = $credit->amount - $credit->used_amount;
@endphp

<li class="flex justify-between">

<span>
{{ $credit->description }}
</span>

<strong class="text-green-600">

R$ {{ number_format($remaining,2,',','.') }}

</strong>

</li>

@endforeach

</ul>

@endif

</div>

</div>

</div>

</x-app-layout>