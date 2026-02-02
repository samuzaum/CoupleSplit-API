<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Nova Despesa
        </h2>
    </x-slot>

    <div class="py-6 max-w-xl mx-auto space-y-4">

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-3 rounded">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('expenses.store') }}" class="space-y-4">
            @csrf

            <input
                name="description"
                value="{{ old('description') }}"
                placeholder="Descrição"
                class="w-full border p-2 rounded"
            >

            <input
                name="amount"
                type="number"
                step="0.01"
                value="{{ old('amount') }}"
                placeholder="Valor"
                class="w-full border p-2 rounded"
            >

            <input
                name="expense_date"
                type="date"
                value="{{ old('expense_date') }}"
                class="w-full border p-2 rounded"
            >

            <select name="card_id" class="w-full border p-2 rounded">
                <option value="">Sem cartão (dinheiro / pix)</option>
                @foreach ($cards as $card)
                    <option value="{{ $card->id }}">
                        {{ $card->name }} ({{ ucfirst($card->type) }})
                    </option>
                @endforeach
            </select>

            <input type="hidden" name="is_shared" value="0">

                <label class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        name="is_shared"
                        value="1"
                        {{ old('is_shared', true) ? 'checked' : '' }}
                    >
                    Despesa compartilhada
                </label>


            <button class="bg-indigo-600 text-white px-4 py-2 rounded">
                Salvar
            </button>
        </form>
    </div>
</x-app-layout>
