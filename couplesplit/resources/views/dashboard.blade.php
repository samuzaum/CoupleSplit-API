<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-6 max-w-3xl mx-auto space-y-6">

        {{-- =====================
            BLOCO DO CASAL
        ===================== --}}
        @php
            $user = auth()->user();
            $couple = $user->couples()->first();
        @endphp

        @if($couple)
            <div class="bg-white p-6 rounded shadow">
                <h3 class="text-lg font-semibold mb-2">
                    {{ $couple->name ?? 'Seu casal' }}
                </h3>

                <p class="text-sm text-gray-600 mb-2">Membros:</p>

                <ul class="list-disc list-inside text-sm">
                    @foreach($couple->users as $member)
                        <li>{{ $member->name }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- =====================
            SITUAÇÃO ENTRE VOCÊS
        ===================== --}}
        <div class="bg-white p-6 rounded shadow text-center">
            <h3 class="text-lg font-semibold mb-2">
                Situação entre vocês
            </h3>

            @if ($netBalance > 0)
                <p class="text-green-700">
                    <strong>{{ $partner->name }}</strong> te deve
                    <strong>R$ {{ number_format($netBalance, 2, ',', '.') }}</strong>
                </p>
            @elseif ($netBalance < 0)
                <p class="text-red-700">
                    Você deve
                    <strong>{{ $partner->name }}</strong>
                    <strong>R$ {{ number_format(abs($netBalance), 2, ',', '.') }}</strong>
                </p>
            @else
                <p class="text-gray-600">
                    Nenhuma pendência entre vocês
                </p>
            @endif

            <div class="mt-4">
                <a href="{{ route('payments.create') }}"
                   class="inline-block px-4 py-2 bg-blue-600 text-white rounded">
                    Registrar pagamento
                </a>
            </div>
        </div>


        {{-- =====================
            DÉBITOS EM ABERTO
        ===================== --}}
        <div class="bg-white p-6 rounded shadow">
            <h3 class="font-semibold mb-3">
                Dívidas em aberto
            </h3>

            @if ($openDebits->isEmpty())
                <p class="text-sm text-gray-600">
                    Nenhuma dívida pendente.
                </p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($openDebits as $debit)
                        @php
                            $remaining = $debit->amount - $debit->used_amount;
                        @endphp

                        <li class="flex justify-between">
                            <span>{{ $debit->description }}</span>
                            <strong>
                                R$ {{ number_format($remaining, 2, ',', '.') }}
                            </strong>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-3 text-sm text-gray-700 text-right">
                    Total em aberto:
                    <strong>
                        R$ {{ number_format(
                            $openDebits->sum(fn($d) => $d->amount - $d->used_amount),
                            2, ',', '.'
                        ) }}
                    </strong>
                </div>
            @endif
        </div>


        {{-- =====================
            CRÉDITOS EM ABERTO
        ===================== --}}
        <div class="bg-white p-6 rounded shadow">
            <h3 class="font-semibold mb-3">
                Créditos a receber
            </h3>

            @if ($openCredits->isEmpty())
                <p class="text-sm text-gray-600">
                    Nenhum crédito pendente.
                </p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($openCredits as $credit)
                        @php
                            $remaining = $credit->amount - $credit->used_amount;
                        @endphp

                        <li class="flex justify-between">
                            <span>{{ $credit->description }}</span>
                            <strong class="text-green-700">
                                R$ {{ number_format($remaining, 2, ',', '.') }}
                            </strong>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

    </div>
</x-app-layout>
