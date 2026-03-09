<x-guest-layout>

<div class="w-full max-w-md mx-auto">

    <div class="bg-white dark:bg-gray-950
                border border-gray-200 dark:border-gray-800
                rounded-3xl
                px-10 py-12
                shadow-sm">

        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold tracking-tight">
                Entrar
            </h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Acesse sua organização financeira.
            </p>
        </div>
        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <!-- Email -->
            <x-text-input
                class="block w-full rounded-xl
                       border-gray-300 dark:border-gray-700
                       bg-gray-50 dark:bg-gray-900
                       focus:border-black dark:focus:border-white
                       focus:ring-0"
                type="email"
                name="email"
                placeholder="Email"
                required autofocus />

            <!-- Senha -->
            <x-text-input
                class="block w-full rounded-xl
                       border-gray-300 dark:border-gray-700
                       bg-gray-50 dark:bg-gray-900
                       focus:border-black dark:focus:border-white
                       focus:ring-0"
                type="password"
                name="password"
                placeholder="Senha"
                required />

            <button type="submit"
                class="w-full py-3 mt-4 rounded-xl
                       bg-black dark:bg-white
                       text-white dark:text-black
                       font-semibold
                       transition duration-200
                       hover:opacity-90">
                Entrar
            </button>
        </form>

        <div class="flex justify-between items-center mt-8 text-sm text-gray-500">
            <a href="{{ route('password.request') }}"
               class="hover:text-black dark:hover:text-white transition">
                Esqueci a senha
            </a>

            <a href="{{ route('register') }}"
               class="hover:text-black dark:hover:text-white transition">
                Criar conta
            </a>
        </div>

    </div>

</div>

</x-guest-layout>