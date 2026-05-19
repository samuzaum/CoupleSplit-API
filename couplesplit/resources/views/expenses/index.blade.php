<x-app-layout>

<div class="mt-12 pb-20 px-6">

<div class="mx-auto max-w-3xl space-y-6">
@if (session('success'))
<div class="bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 p-4 rounded-xl text-sm">{{ session('success') }}</div>
@endif
@if (session('error'))
<div class="bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 p-4 rounded-xl text-sm">{{ session('error') }}</div>
@endif
@if (session('budget_warning'))
<div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 text-yellow-800 dark:text-yellow-300 p-4 rounded-xl text-sm">
    ⚠ {{ session('budget_warning') }}
</div>
@endif

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
FILTROS DE BUSCA
====================== --}}
@php
$filterRoute = match($context) {
    'couple'   => route('expenses.couple'),
    'personal' => route('expenses.personal'),
    default    => route('expenses.index'),
};
@endphp
<form method="GET" action="{{ $filterRoute }}" class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-2xl p-5
flex flex-wrap gap-3 items-end
">

<div class="flex-1 min-w-40">
    <input
        name="q"
        value="{{ $filters['q'] ?? '' }}"
        placeholder="Buscar descrição..."
        class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white text-sm"
    />
</div>

<div class="min-w-36">
    <select name="category" class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white text-sm">
        <option value="">Todas as categorias</option>
        @foreach ($allCats as $cat)
            <option value="{{ $cat }}" {{ ($filters['category'] ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
        @endforeach
    </select>
</div>

<div class="min-w-36">
    <input
        name="month"
        type="month"
        value="{{ $filters['month'] ?? '' }}"
        class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white text-sm"
    />
</div>

<button type="submit" class="px-5 py-2 rounded-full bg-black text-white dark:bg-white dark:text-black text-sm font-semibold">
    Filtrar
</button>

@if (array_filter($filters))
<a href="{{ request()->url() }}" class="px-4 py-2 rounded-full border border-gray-300 dark:border-gray-700 text-sm text-gray-500">
    Limpar
</a>
@endif
</form>


{{-- ======================
BOTÃO NOVA DESPESA + EXPORTAR
====================== --}}
<div class="flex items-center gap-3 flex-wrap">

<a href="{{ route('expenses.create') }}"
class="inline-block px-5 py-2 rounded-full bg-black text-white dark:bg-white dark:text-black font-semibold">
Nova despesa
</a>

<a href="{{ route('export.expenses', array_filter($filters) + ($context !== 'all' ? ['type' => $context] : [])) }}"
class="inline-block px-4 py-2 rounded-full border border-gray-300 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400">
↓ Exportar CSV
</a>

</div>



{{-- ======================
LISTAGEM
====================== --}}
@if($expenses->total() === 0)

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
flex justify-between items-start gap-3
">

@php
$isMe       = $expense->paid_by === auth()->id();
$ratio      = $expense->split_ratio ?? 0.5;
$myShare    = $expense->is_shared
    ? round($expense->amount * ($isMe ? $ratio : (1 - $ratio)), 2)
    : $expense->amount;
$balance    = $isMe ? ($myCredits[$expense->id] ?? null) : ($myDebits[$expense->id] ?? null);
$shareIsPaid = $balance ? ($balance->used_amount >= $balance->amount) : false;
@endphp

<div>

<p class="font-medium text-black dark:text-white">
{{ $expense->description }}
</p>

<p class="text-xs text-gray-500">
{{ $expense->expense_date?->format('d/m/Y') }}
• {{ $expense->payer?->name ?? 'Desconhecido' }}
@if ($expense->is_shared)
    • Compartilhada ({{ round($ratio * 100) }}/{{ 100 - round($ratio * 100) }})
@else
    • Pessoal
@endif
@if ($expense->is_recurring) • <span class="text-blue-500">Recorrente</span> @endif
@if ($expense->parent_id) • <span class="text-gray-400">Gerada auto</span> @endif
@if (!$expense->is_shared)
    •
    @if ($expense->paid_at)
        <span class="text-green-600">Paga em {{ $expense->paid_at->format('d/m/Y') }}</span>
    @else
        <span class="text-yellow-600">Pendente</span>
    @endif
@endif
</p>
@if (!$expense->is_shared && $isMe)
<div class="mt-1">
    @if ($expense->paid_at)
    <form method="POST" action="{{ route('expenses.mark-unpaid', $expense) }}">
        @csrf
        <button type="submit" class="text-xs text-gray-400 hover:text-yellow-600">
            Marcar como pendente
        </button>
    </form>
    @else
    <form method="POST" action="{{ route('expenses.mark-paid', $expense) }}">
        @csrf
        <button type="submit" class="text-xs text-green-500 hover:text-green-700 font-medium">
            ✓ Marcar como paga
        </button>
    </form>
    @endif
</div>
@endif
@if ($expense->notes)
<p class="text-xs text-gray-400 mt-0.5 italic">{{ $expense->notes }}</p>
@endif

@if ($expense->is_shared)
<p class="text-xs mt-1">
    @if ($isMe)
        <span class="text-gray-500">Sua parte: R$ {{ number_format($myShare, 2, ',', '.') }}</span>
        <span class="ml-2 {{ $shareIsPaid ? 'text-green-600' : 'text-yellow-600' }}">
            {{ $shareIsPaid ? '✓ recebido' : '· aguardando' }}
        </span>
    @else
        <span class="font-medium {{ $shareIsPaid ? 'text-green-600' : 'text-red-500' }}">
            Sua parte: R$ {{ number_format($myShare, 2, ',', '.') }}
            {{ $shareIsPaid ? '· pago ✓' : '· em aberto' }}
        </span>
    @endif
</p>
@endif
@if ($expense->status === 'disputed')
<span class="inline-block mt-1 text-xs font-semibold text-orange-600 bg-orange-50 dark:bg-orange-900/20 px-2 py-0.5 rounded-full">
    ⚠ Contestada
</span>
@endif

</div>


<div class="flex flex-col items-end gap-1.5 shrink-0">

<div class="text-right">
<strong class="text-lg block">
R$ {{ number_format($expense->amount,2,',','.') }}
</strong>
@if ($expense->is_shared && $myShare != $expense->amount)
<span class="text-xs text-gray-400">sua parte: R$ {{ number_format($myShare, 2, ',', '.') }}</span>
@endif
</div>

<div class="flex items-center gap-3">

<a href="{{ route('expenses.edit', $expense) }}"
class="text-xs text-gray-400 hover:text-black dark:hover:text-white">
Editar
</a>

@if ($expense->is_shared && !$isMe)
    @if ($expense->status === 'disputed')
    <form method="POST" action="{{ route('expenses.undispute', $expense) }}">
        @csrf
        <button type="submit" class="text-xs text-orange-500 hover:text-orange-700">
            Desfazer
        </button>
    </form>
    @else
    <form method="POST" action="{{ route('expenses.dispute', $expense) }}">
        @csrf
        <button type="submit" class="text-xs text-orange-400 hover:text-orange-600">
            Contestar
        </button>
    </form>
    @endif
@endif

<form method="POST" action="{{ route('expenses.destroy', $expense) }}"
onsubmit="return confirm('Excluir esta despesa?')">
@csrf
@method('DELETE')
<button type="submit" class="text-xs text-red-400 hover:text-red-600">
Excluir
</button>
</form>

</div>

</div>

</div>

@endforeach

</div>


@if ($expenses->hasPages())
<div class="flex justify-center">
    {{ $expenses->links() }}
</div>
@endif

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
Total {{ $expenses->total() > 20 ? '(todas as despesas filtradas)' : '' }}
</p>

<p class="text-2xl font-bold text-black dark:text-white">

R$ {{ number_format($total,2,',','.') }}

</p>

</div>

@endif



{{-- ======================
ORÇAMENTOS POR CATEGORIA
====================== --}}
<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl p-8
">
<h3 class="font-semibold mb-4 text-black dark:text-white">Orçamentos mensais</h3>

@if (session('budget_success'))
<div class="bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 p-3 rounded-xl text-sm mb-4">{{ session('budget_success') }}</div>
@endif

<form method="POST" action="{{ route('budgets.store') }}" class="flex flex-wrap gap-3 mb-6">
@csrf
<select name="category" required
    class="flex-1 min-w-36 px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white text-sm">
    <option value="">Categoria</option>
    @foreach ($allCats as $cat)
        <option value="{{ $cat }}">{{ $cat }}</option>
    @endforeach
</select>
<input name="amount" type="number" step="0.01" min="1" placeholder="Limite R$" required
    class="w-36 px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white text-sm"
/>
<button type="submit" class="px-5 py-2 rounded-full bg-black text-white dark:bg-white dark:text-black text-sm font-semibold">
    Salvar
</button>
</form>

@php $budgets = $couple->budgets()->get(); @endphp
@if ($budgets->isNotEmpty())
<ul class="space-y-3 text-sm">
@foreach ($budgets as $budget)
<li>
    <div class="flex justify-between items-center">
        <span class="text-black dark:text-white font-medium">{{ $budget->category }}</span>
        <div class="flex items-center gap-3">
            <button type="button"
                onclick="toggleBudgetEdit({{ $budget->id }})"
                class="text-xs text-gray-400 hover:text-black dark:hover:text-white">
                Editar
            </button>
            <form method="POST" action="{{ route('budgets.destroy', $budget) }}"
                onsubmit="return confirm('Remover orçamento de {{ $budget->category }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-500 text-xs">Remover</button>
            </form>
        </div>
    </div>
    <p class="text-xs text-gray-500 mt-0.5">R$ {{ number_format($budget->amount, 2, ',', '.') }}/mês</p>
    <form id="budget_edit_{{ $budget->id }}" method="POST"
        action="{{ route('budgets.update', $budget) }}"
        class="hidden mt-2 flex gap-2 items-center">
        @csrf
        @method('PATCH')
        <input type="number" name="amount" step="0.01" min="1"
            value="{{ $budget->amount }}"
            class="w-32 px-3 py-1.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white text-sm"
        />
        <button type="submit"
            class="px-4 py-1.5 rounded-full bg-black text-white dark:bg-white dark:text-black text-xs font-semibold">
            Salvar
        </button>
        <button type="button"
            onclick="toggleBudgetEdit({{ $budget->id }})"
            class="text-xs text-gray-400">
            Cancelar
        </button>
    </form>
</li>
@endforeach
</ul>
<script>
function toggleBudgetEdit(id) {
    var el = document.getElementById('budget_edit_' + id);
    el.classList.toggle('hidden');
    el.classList.toggle('flex');
}
</script>
@else
<p class="text-sm text-gray-500">Nenhum orçamento definido ainda.</p>
@endif
</div>


{{-- ======================
CATEGORIAS PERSONALIZADAS
====================== --}}
<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl
p-8
">

<h3 class="font-semibold mb-4 text-black dark:text-white">
Categorias personalizadas
</h3>

@if (session('cat_success'))
<div class="bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 p-3 rounded-xl text-sm mb-4">{{ session('cat_success') }}</div>
@endif
@if (session('cat_error'))
<div class="bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 p-3 rounded-xl text-sm mb-4">{{ session('cat_error') }}</div>
@endif

<form method="POST" action="{{ route('categories.store') }}" class="flex gap-3 mb-6">
@csrf
<input
    name="name"
    placeholder="Nova categoria"
    maxlength="50"
    required
    class="flex-1 px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white text-sm"
/>
<button type="submit" class="px-5 py-2 rounded-full bg-black text-white dark:bg-white dark:text-black text-sm font-semibold">
    Adicionar
</button>
</form>

@if ($customCats->isNotEmpty())
<ul class="space-y-2 text-sm">
@foreach ($customCats as $cat)
<li class="flex justify-between items-center">
    <span class="text-black dark:text-white">{{ $cat->name }}</span>
    <form method="POST" action="{{ route('categories.destroy', $cat) }}"
        onsubmit="return confirm('Remover a categoria \'{{ $cat->name }}\'?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-red-500 text-xs">Remover</button>
    </form>
</li>
@endforeach
</ul>
@else
<p class="text-sm text-gray-500">Nenhuma categoria personalizada criada.</p>
@endif

</div>

</div>

</div>

</x-app-layout>