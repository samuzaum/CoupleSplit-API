<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto">
        @php
            $user = auth()->user();
            $couple = $user->couples()->first();
        @endphp

        @if($couple)
            <div class="bg-white p-6 rounded shadow">
                <h3 class="text-lg font-semibold mb-2">
                    {{ $couple->name ?? 'Seu casal' }}
                </h3>

                <p class="text-sm text-gray-600 mb-4">
                    Membros:
                </p>

                <ul class="list-disc list-inside">
                    @foreach($couple->users as $member)
                        <li>{{ $member->name }}</li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="bg-yellow-100 p-6 rounded">
                <p class="mb-4">
                    Você ainda não faz parte de um casal.
                </p>

                <a
                    href="{{ route('couples.create') }}"
                    class="text-indigo-600 font-semibold"
                >
                    Criar casal
                </a>
            </div>
        @endif
    </div>
    @if(isset($netBalance) && $partner)
        <div class="py-6 max-w-3xl mx-auto text-center
            {{ $netBalance > 0 ? 'bg-green-100 text-green-800' 
            : ($netBalance < 0 ? 'bg-red-100 text-red-800' 
            : 'bg-gray-100 text-gray-700') }}">

            @if($netBalance > 0)
                <strong>{{ $partner->name }}</strong> te deve
                <strong>R$ {{ number_format($netBalance, 2, ',', '.') }}</strong>
            @elseif($netBalance < 0)
                Você deve
                <strong>{{ $partner->name }}</strong>
                <strong>R$ {{ number_format(abs($netBalance), 2, ',', '.') }}</strong>
            @else
                Tudo certo! Nenhuma pendência entre vocês
            @endif

        </div>
    @endif
    @if(isset($netBalance) && $netBalance > 0)
        <form method="POST" action="{{ route('debts.settle') }}" class="mt-4">
            @csrf
            <button type="submit"
                class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                Marcar como pago
            </button>
        </form>
    @endif
</x-app-layout>