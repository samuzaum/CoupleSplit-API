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
</x-app-layout>
