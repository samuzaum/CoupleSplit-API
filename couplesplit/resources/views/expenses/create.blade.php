<x-app-layout>

<div class="mt-12 pb-20 px-6">

<div class="mx-auto max-w-xl">

<div class="
bg-white dark:bg-black
border border-gray-200 dark:border-gray-800
rounded-3xl
shadow-sm
p-6 sm:p-10
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


{{-- observação --}}
<textarea
name="notes"
placeholder="Observação (opcional) — ex: nota fiscal, restaurante tal..."
rows="2"
class="
w-full
px-4 py-3
rounded-xl
border border-gray-300 dark:border-gray-700
bg-white dark:bg-black
text-black dark:text-white
resize-none
">{{ old('notes') }}</textarea>

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


{{-- categoria --}}
<select
name="category"
required
class="
w-full
px-4 py-3
rounded-xl
border border-gray-300 dark:border-gray-700
bg-white dark:bg-black
text-black dark:text-white
">
<option value="" disabled selected>Selecione uma categoria</option>
@foreach (\App\Models\Expense::CATEGORIES as $cat)
<option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
@endforeach
@if ($customCats->isNotEmpty())
<optgroup label="Personalizadas">
@foreach ($customCats as $cat)
<option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
@endforeach
</optgroup>
@endif
</select>


{{-- quem pagou (opcional, padrão = você) --}}
@if ($partner)
<input type="hidden" name="paid_by_partner" id="paid_by_partner_input" value="0">
<div class="flex items-center justify-between">
    <span class="text-sm text-gray-500 dark:text-gray-400">Quem pagou?</span>
    <div class="flex rounded-full border border-gray-300 dark:border-gray-700 overflow-hidden text-sm font-medium">
        <button type="button" id="payer_me"
            onclick="setPayer('me')"
            class="px-4 py-1.5 bg-black text-white dark:bg-white dark:text-black transition">
            Eu
        </button>
        <button type="button" id="payer_partner"
            onclick="setPayer('partner')"
            class="px-4 py-1.5 text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white transition">
            {{ $partner->name }}
        </button>
    </div>
</div>
@endif

{{-- cartão --}}
<select
id="card_select"
name="card_id"
class="
w-full
px-4 py-3
rounded-xl
border border-gray-300 dark:border-gray-700
bg-white dark:bg-black
text-black dark:text-white
">
<option value="" data-type="" data-owner="me">Sem cartão (dinheiro / pix)</option>

@foreach ($myCards as $card)
<option value="{{ $card->id }}" data-type="{{ $card->type }}" data-owner="me">
    {{ $card->name }} ({{ ucfirst($card->type) }})
</option>
@endforeach

@if ($partnerCards->isNotEmpty())
<optgroup id="partner_cards_group" label="Cartões de {{ $partner->name ?? 'parceiro(a)' }}" class="hidden">
    @foreach ($partnerCards as $card)
    <option value="{{ $card->id }}" data-type="{{ $card->type }}" data-owner="partner" class="partner-card-option hidden">
        {{ $card->name }} ({{ ucfirst($card->type) }})
    </option>
    @endforeach
</optgroup>
@endif
</select>


{{-- aviso pix/dinheiro --}}
<div id="pix_notice" class="text-xs text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl px-4 py-3">
    ✓ Pix / dinheiro — será marcado como <strong>pago</strong> automaticamente.
</div>

{{-- parcelas (visível só com cartão de crédito) --}}
<div id="installments_wrapper" style="display:none">
<input
id="installments_input"
name="installments"
type="number"
min="1"
max="48"
value="{{ old('installments', 1) }}"
placeholder="Parcelas"

class="
w-full
px-4 py-3
rounded-xl
border border-gray-300 dark:border-gray-700
bg-white dark:bg-black
text-black dark:text-white
"
/>
</div>

<script>
(function () {
    var select  = document.getElementById('card_select');
    var wrapper = document.getElementById('installments_wrapper');
    var input   = document.getElementById('installments_input');
    var notice  = document.getElementById('pix_notice');

    function toggle() {
        var opt      = select.options[select.selectedIndex];
        var type     = opt ? opt.dataset.type : '';
        var isCredit = type === 'credit';
        var hasCard  = select.value !== '';

        wrapper.style.display = isCredit ? '' : 'none';
        notice.style.display  = hasCard ? 'none' : '';

        if (!isCredit) input.value = 1;
    }

    select.addEventListener('change', toggle);
    toggle();
})();

function setPayer(who) {
    var isPartner = who === 'partner';

    document.getElementById('paid_by_partner_input').value = isPartner ? '1' : '0';

    // Estilo dos botões
    var activeClass   = 'px-4 py-1.5 bg-black text-white dark:bg-white dark:text-black transition';
    var inactiveClass = 'px-4 py-1.5 text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white transition';
    document.getElementById('payer_me').className      = isPartner ? inactiveClass : activeClass;
    document.getElementById('payer_partner').className = isPartner ? activeClass    : inactiveClass;

    // Mostra/esconde cartões do parceiro
    var partnerGroup = document.getElementById('partner_cards_group');
    document.querySelectorAll('.partner-card-option').forEach(function(opt) {
        opt.hidden = !isPartner;
    });
    if (partnerGroup) partnerGroup.hidden = !isPartner;

    // Esconde/mostra cartões do próprio usuário
    document.querySelectorAll('[data-owner="me"]').forEach(function(opt) {
        opt.hidden = isPartner && opt.value !== '';
    });

    // Reset seleção pro primeiro disponível
    select.value = '';
    select.dispatchEvent(new Event('change'));
}
</script>


<input type="hidden" name="is_shared" value="0">

<label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300">
<input
    type="checkbox"
    id="is_shared_check"
    name="is_shared"
    value="1"
    {{ old('is_shared', true) ? 'checked' : '' }}
    class="rounded border-gray-300"
    onchange="toggleSplitRatio()"
/>
Despesa compartilhada
</label>

@if (!$myIncome || !$partnerIncome)
<div id="income_warning" class="text-xs text-yellow-700 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl px-4 py-3">
    @if (!$myIncome && !$partnerIncome)
        Nenhum dos dois tem renda cadastrada. O split será 50/50.
    @elseif (!$myIncome)
        Você não tem renda cadastrada. O split será 50/50.
    @else
        {{ $partner?->name ?? 'Parceiro(a)' }} não tem renda cadastrada. O split será 50/50.
    @endif
    <a href="{{ route('profile.edit') }}" class="underline ml-1">Cadastrar renda →</a>
</div>
@endif

{{-- split ratio (visível só se compartilhada) --}}
<div id="split_ratio_wrapper">
<label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">
    Sua parte: <span id="ratio_display">50</span>% &nbsp;|&nbsp;
    {{ $partner?->name ?? 'Parceiro(a)' }}: <span id="ratio_other">50</span>%
</label>
<input
    id="split_ratio_input"
    name="split_ratio"
    type="range"
    min="1"
    max="99"
    value="{{ old('split_ratio', 50) }}"
    oninput="updateRatio(this.value)"
    class="w-full"
/>

@if ($incomeRatio)
<button type="button" onclick="applyIncomeRatio({{ $incomeRatio }})"
    class="mt-2 text-xs px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-black dark:hover:border-white">
    Usar proporção de renda
    ({{ $incomeRatio }}% / {{ 100 - $incomeRatio }}%)
</button>
@else
<p class="mt-2 text-xs text-gray-400">
    Para usar a proporção de renda, cadastre a renda de ambos no
    <a href="{{ route('profile.edit') }}" class="underline">perfil</a>.
</p>
@endif
</div>

<label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300">
<input
    type="checkbox"
    name="is_recurring"
    value="1"
    {{ old('is_recurring') ? 'checked' : '' }}
    class="rounded border-gray-300"
/>
Despesa recorrente (repete todo mês)
</label>

<script>
function updateRatio(val) {
    val = parseInt(val);
    document.getElementById('ratio_display').textContent = val;
    document.getElementById('ratio_other').textContent   = 100 - val;
    document.getElementById('split_ratio_input').value   = val;
}
function applyIncomeRatio(ratio) {
    updateRatio(ratio);
}
function toggleSplitRatio() {
    var shared = document.getElementById('is_shared_check').checked;
    document.getElementById('split_ratio_wrapper').style.display = shared ? '' : 'none';
    var warn = document.getElementById('income_warning');
    if (warn) warn.style.display = shared ? '' : 'none';
}
toggleSplitRatio();
</script>


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