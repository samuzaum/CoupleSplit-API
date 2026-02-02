<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Meus Cartões
        </h2>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto space-y-4">
        <a href="{{ route('cards.create') }}" class="text-indigo-600 font-semibold">
            + Novo cartão
        </a>

        @forelse($cards as $card)
            <div class="bg-white p-4 rounded shadow">
                <strong>{{ $card->name }}</strong>
                <div class="text-sm text-gray-600">
                    {{ ucfirst($card->type) }}
                    @if($card->closing_day)
                        · Fecha dia {{ $card->closing_day }}
                    @endif
                </div>
                <a
                    href="{{ route('cards.edit', $card) }}"
                    class="text-indigo-600 text-sm font-semibold"
                >
                    Editar
                </a>
            </div>
        @empty
            <p class="text-gray-600">Nenhum cartão cadastrado.</p>
        @endforelse
    </div>
</x-app-layout>
