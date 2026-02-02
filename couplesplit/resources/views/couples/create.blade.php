<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Criar Casal
        </h2>
    </x-slot>

    <div class="py-6 max-w-xl mx-auto">
        <form method="POST" action="{{ route('couples.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="couple_name" class="block text-sm font-medium">Nome do casal (opcional)</label>
                <input
                    type="text"
                    name="name"
                    id="couple_name"
                    class="mt-1 block w-full border rounded px-3 py-2"
                    placeholder="Ex: Samuel & Evelyn"
                >
            </div>

            <button
                type="submit"
                class="bg-indigo-600 text-white px-4 py-2 rounded"
            >
                Criar Casal
            </button>
        </form>
    </div>
</x-app-layout>

