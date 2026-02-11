<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Registrar pagamento
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-6">
        <form
            method="POST"
            action="{{ route('payments.store') }}"
            class="bg-white p-6 rounded shadow space-y-6"
        >
            @csrf

            <p class="text-sm text-gray-600">
                Pagamento entre você e
                <strong>{{ $partner->name }}</strong>
            </p>

            {{-- VALOR --}}
            <div>
                <label class="block text-sm font-medium mb-1">
                    Valor do pagamento
                </label>
                <input
                    type="number"
                    name="amount"
                    step="0.01"
                    min="0.01"
                    required
                    class="w-full border rounded px-3 py-2"
                >
            </div>

            {{-- DÉBITOS EM ABERTO (VISUAL) --}}
            <div>
                <p class="font-semibold mb-2">
                    Dívidas em aberto (informativo)
                </p>

                @if ($openDebits->isEmpty())
                    <p class="text-sm text-gray-600">
                        Nenhuma dívida em aberto 🎉
                    </p>
                @else
                    <ul class="space-y-1 text-sm">
                        @foreach ($openDebits as $debit)
                            <li class="flex justify-between">
                                <span>
                                    {{ $debit->description ?? 'Despesa' }}
                                </span>
                                <span>
                                    R$
                                    {{ number_format($debit->amount - $debit->used_amount, 2, ',', '.') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <p class="mt-2 text-sm text-gray-700">
                        Total em aberto:
                        <strong>
                            R$ {{ number_format($openDebits->sum(fn($d) => $d->amount - $d->used_amount), 2, ',', '.') }}
                        </strong>
                    </p>
                @endif
            </div>

            {{-- AVISO IMPORTANTE --}}
            <div class="bg-blue-50 p-3 rounded text-sm text-blue-800">
                O valor pago será automaticamente abatido das dívidas mais antigas.
                Caso o valor seja maior, o saldo vira crédito.
            </div>

            {{-- BOTÃO --}}
            <div class="pt-4">
                <button
                    class="px-4 py-2 bg-blue-600 text-white rounded"
                >
                    Confirmar pagamento
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
