<x-app-layout>

<div class="mt-12 pb-20 px-6">
<div class="mx-auto max-w-xl space-y-6">

<h1 class="text-3xl font-bold text-black dark:text-white">Seu casal</h1>

@if (session('success'))
<div class="bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 p-4 rounded-xl text-sm">
    {{ session('success') }}
</div>
@endif

{{-- Membros --}}
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-8 space-y-4">
    <h3 class="font-semibold text-black dark:text-white">
        {{ $couple->name ?? 'Casal' }}
    </h3>

    <div class="space-y-3">
        @foreach ($couple->users as $member)
        @php $isMe = $member->id === auth()->id(); @endphp
        <div class="flex items-center gap-3">
            <span class="w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                </svg>
            </span>
            <div>
                <p class="text-sm font-medium text-black dark:text-white">
                    {{ $member->name }}
                    @if ($isMe) <span class="text-xs text-gray-400">(você)</span> @endif
                </p>
                <p class="text-xs text-gray-400">{{ $member->email }}</p>
            </div>
        </div>
        @endforeach

        @if (!$partner)
        <p class="text-sm text-gray-400 italic">Aguardando parceiro(a) entrar...</p>
        @endif
    </div>
</div>

{{-- Convidar parceiro --}}
@if (!$partner)
<div class="bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-3xl p-8">
    <h3 class="font-semibold text-black dark:text-white mb-4">Convidar parceiro(a)</h3>
    <form method="POST" action="{{ route('couples.invite', $couple) }}" class="flex gap-3">
        @csrf
        <input type="email" name="email" placeholder="E-mail do parceiro(a)"
            class="flex-1 px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-black text-black dark:text-white text-sm"
        />
        <button type="submit"
            class="px-5 py-2 rounded-full bg-black text-white dark:bg-white dark:text-black text-sm font-semibold">
            Convidar
        </button>
    </form>
</div>
@endif

{{-- Zona de perigo --}}
<div class="bg-white dark:bg-black border border-red-200 dark:border-red-900 rounded-3xl p-8">
    <h3 class="font-semibold text-red-600 dark:text-red-400 mb-2">Zona de perigo</h3>
    <p class="text-sm text-gray-500 mb-4">
        Ao sair do casal, você perde acesso às despesas e pagamentos compartilhados.
        Se você for o único membro, o casal será excluído permanentemente.
    </p>
    <form method="POST" action="{{ route('couple.leave') }}"
        onsubmit="return confirm('Tem certeza que quer sair do casal? Esta ação não pode ser desfeita.')">
        @csrf
        <button type="submit"
            class="px-5 py-2 rounded-full border border-red-400 dark:border-red-600 text-red-600 dark:text-red-400 text-sm font-semibold hover:bg-red-50 dark:hover:bg-red-950 transition">
            Sair do casal
        </button>
    </form>
</div>

</div>
</div>

</x-app-layout>
