<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl space-y-8">
                    @include('profile.partials.update-profile-information-form')

                    {{-- DIVISOR --}}
                    <hr class="border-gray-200 dark:border-gray-700">

                    {{-- BENEFÍCIOS --}}
                    <div class="space-y-5">
                        <div>
                            <h2 class="text-base font-medium text-gray-900 dark:text-gray-100">Benefícios</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Vale alimentação, refeição, transporte, etc. Não geram débito entre o casal.
                            </p>
                        </div>

                        @if (session('success'))
                            <p class="text-sm text-green-600 dark:text-green-400">{{ session('success') }}</p>
                        @endif

                        {{-- Lista --}}
                        @if ($benefits->isNotEmpty())
                        <div class="space-y-2">
                            @foreach ($benefits as $b)
                            <div class="flex items-center justify-between gap-4 p-3 rounded-xl border border-gray-200 dark:border-gray-700">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-black dark:text-white">{{ $b->name }}</p>
                                        @if ($b->is_couple)
                                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">casal</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        R$ {{ number_format($b->monthly_amount, 2, ',', '.') }}/mês
                                        · Usado: R$ {{ number_format($b->used, 2, ',', '.') }}
                                        · <span class="{{ $b->remaining > 0 ? 'text-green-600' : 'text-red-500' }}">R$ {{ number_format($b->remaining, 2, ',', '.') }} restante</span>
                                    </p>
                                </div>
                                @if ($b->user_id === auth()->id())
                                <form method="POST" action="{{ route('benefits.destroy', $b) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-400 hover:text-red-600">Remover</button>
                                </form>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @else
                            <p class="text-sm text-gray-400">Nenhum benefício cadastrado.</p>
                        @endif

                        {{-- Adicionar --}}
                        <form method="POST" action="{{ route('benefits.store') }}" class="space-y-3">
                            @csrf
                            <div class="flex gap-3 flex-wrap">
                                <input name="name" placeholder="Ex: Vale Alimentação" required
                                    class="flex-1 min-w-40 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-black dark:text-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
                                <input name="monthly_amount" type="number" step="0.01" min="1" placeholder="Valor mensal" required
                                    class="w-36 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-black dark:text-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-black dark:focus:ring-white">
                            </div>
                            <div class="flex items-center justify-between flex-wrap gap-3">
                                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                                    <input type="checkbox" name="is_couple" value="1" class="rounded border-gray-300">
                                    Compartilhado com o casal
                                </label>
                                <button type="submit"
                                    class="px-5 py-2 rounded-full bg-black text-white dark:bg-white dark:text-black text-sm font-semibold hover:opacity-90 transition">
                                    Adicionar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
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
