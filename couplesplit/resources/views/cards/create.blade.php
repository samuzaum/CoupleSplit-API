<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Novo Cartão
        </h2>
    </x-slot>

    <div class="py-6 max-w-xl mx-auto">
        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('cards.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium">Nome</label>
                <input
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    class="mt-1 w-full border rounded px-3 py-2"
                    placeholder="Nubank"
                />
            </div>

            <div>
                <label for="type" class="block text-sm font-medium">Tipo</label>
                <select id="type" name="type" class="mt-1 w-full border rounded px-3 py-2">
                    <option value="credit" @selected(old('type') === 'credit')>Crédito</option>
                    <option value="debit" @selected(old('type') === 'debit')>Débito</option>
                </select>
            </div>

            <div id="closing-day-field">
                <label for="closing_day" class="block text-sm font-medium">
                    Dia de fechamento
                </label>
                <input
                    id="closing_day"
                    name="closing_day"
                    type="number"
                    min="1"
                    max="31"
                    value="{{ old('closing_day') }}"
                    class="mt-1 w-full border rounded px-3 py-2"
                >
            </div>

            <button class="bg-indigo-600 text-white px-4 py-2 rounded">
                Salvar
            </button>
        </form>
    </div>
    <script>
        const typeSelect = document.getElementById('type');
        const closingField = document.getElementById('closing-day-field');

        function toggleClosingDay() {
            if (typeSelect.value === 'credit') {
                closingField.style.display = 'block';
            } else {
                closingField.style.display = 'none';
            }
        }

        typeSelect.addEventListener('change', toggleClosingDay);
        toggleClosingDay();
    </script>
</x-app-layout>
