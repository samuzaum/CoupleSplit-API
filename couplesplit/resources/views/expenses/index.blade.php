<x-app-layout>
    <h1 class="text-xl font-bold mb-4">Despesas</h1>
    <div class="mb-4 border-b border-gray-200">
    <nav class="-mb-px flex space-x-6">

        <a href="{{ route('expenses.index') }}"
           class="pb-2 border-b-2 text-sm font-medium
           {{ $context === 'all' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Todas
        </a>

        <a href="{{ route('expenses.couple') }}"
           class="pb-2 border-b-2 text-sm font-medium
           {{ $context === 'couple' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Casal
        </a>

        <a href="{{ route('expenses.personal') }}"
           class="pb-2 border-b-2 text-sm font-medium
           {{ $context === 'personal' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            Pessoal
        </a>

    </nav>
</div>
    <div class="mb-4">
        <a href="{{ route('expenses.create') }}"
           class="inline-block px-4 py-2 bg-blue-600 text-white rounded">
             Nova despesa
        </a>
    </div>

    @if($expenses->isEmpty())
        <p>Nenhuma despesa registrada ainda.</p>
    @else
        <table class="w-full border-collapse bg-white rounded shadow">
            <thead>
                <tr class="border-b">
                    <th class="p-2 text-left">Descrição</th>
                    <th class="p-2">Valor</th>
                    <th class="p-2">Data</th>
                    <th class="p-2">Tipo</th>
                    <th class="p-2">Pago por</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $expense)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-2">{{ $expense->description }}</td>
                        <td class="p-2 text-center">
                            R$ {{ number_format($expense->amount, 2, ',', '.') }}
                        </td>
                        <td class="p-2 text-center">
                            {{ $expense->expense_date->format('d/m/Y') }}
                        </td>
                        <td class="p-2 text-center">
                            {{ $expense->is_shared ? 'Compartilhada' : 'Pessoal' }}
                        </td>
                        <td class="p-2 text-center">
                            {{ $expense->payer->name }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mb-4 text-gray-700">
            <strong>Total:</strong>
            R$ {{ number_format($total, 2, ',', '.') }}
        </div>
    @endif
</x-app-layout>
