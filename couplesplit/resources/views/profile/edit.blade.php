<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            {{-- BENEFÍCIOS --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl space-y-5">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Benefícios</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Vale alimentação, refeição, transporte, etc. Despesas pagas com benefício não geram débito entre o casal.
                        </p>
                    </div>

                    @if (session('success'))
                        <p class="text-sm text-green-600 dark:text-green-400">{{ session('success') }}</p>
                    @endif

                    {{-- Lista --}}
                    @if ($benefits->isNotEmpty())
                    <div class="space-y-3">
                        @foreach ($benefits as $b)
                        <div class="flex items-center justify-between gap-4 p-4 rounded-2xl border border-gray-200 dark:border-gray-700">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-black dark:text-white">{{ $b->name }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    R$ {{ number_format($b->monthly_amount, 2, ',', '.') }}/mês
                                    · Usado: R$ {{ number_format($b->used, 2, ',', '.') }}
                                    · Restante: <span class="{{ $b->remaining > 0 ? 'text-green-600' : 'text-red-500' }}">R$ {{ number_format($b->remaining, 2, ',', '.') }}</span>
                                </p>
                            </div>
                            <form method="POST" action="{{ route('benefits.destroy', $b) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-400 hover:text-red-600">Remover</button>
                            </form>
                        </div>
                        @endforeach
                    </div>
                    @else
                        <p class="text-sm text-gray-400">Nenhum benefício cadastrado.</p>
                    @endif

                    {{-- Adicionar --}}
                    <form method="POST" action="{{ route('benefits.store') }}" class="flex gap-3 flex-wrap">
                        @csrf
                        <input name="name" placeholder="Ex: Vale Alimentação" required
                            class="flex-1 min-w-40 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-black dark:text-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
                        <input name="monthly_amount" type="number" step="0.01" min="1" placeholder="Valor mensal" required
                            class="w-36 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-black dark:text-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
                        <button type="submit"
                            class="px-5 py-2 rounded-full bg-black text-white dark:bg-white dark:text-black text-sm font-semibold hover:opacity-90 transition">
                            Adicionar
                        </button>
                    </form>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
