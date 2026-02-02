<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Editar Cartão
        </h2>
    </x-slot>

    <div class="py-6 max-w-xl mx-auto">
        <form method="POST" action="{{ route('cards.update', $card) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name">Nome</label>
                <input
                    id="name"
                    name="name"
                    value="{{ old('name', $card->name) }}"
                    class="w-full border p-2 rounded"
                >
            </div>

            <div>
                <label for="type">Tipo</label>
                <select id="type" name="type" class="w-full border p-2 rounded">
                    <option value="credit" @selected($card->type === 'credit')>Crédito</option>
                    <option value="debit" @selected($card->type === 'debit')>Débito</option>
                </select>
            </div>

            <div id="closing-day-field">
                <label for="closing_day">Dia de fechamento</label>
                <input
                    id="closing_day"
                    name="closing_day"
                    type="number"
                    min="1"
                    max="31"
                    value="{{ old('closing_day', $card->closing_day) }}"
                    class="w-full border p-2 rounded"
                >
            </div>

            <button class="bg-indigo-600 text-white px-4 py-2 rounded">
                Atualizar
            </button>
        </form>

        <form
            method="POST"
            action="{{ route('cards.destroy', $card) }}"
            class="mt-4"
            onsubmit="return confirm('Tem certeza que deseja excluir este cartão?')"
        >
            @csrf
            @method('DELETE')

            <button class="text-red-600 font-semibold">
                Excluir cartão
            </button>
        </form>
    </div>

    <script>
        const typeSelect = document.getElementById('type');
        const closingField = document.getElementById('closing-day-field');

        function toggleClosingDay() {
            closingField.style.display = typeSelect.value === 'credit'
                ? 'block'
                : 'none';
        }

        typeSelect.addEventListener('change', toggleClosingDay);
        toggleClosingDay();
    </script>
</x-app-layout>
