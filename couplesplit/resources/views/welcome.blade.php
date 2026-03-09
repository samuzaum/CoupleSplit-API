<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Couple Split') }}</title>

    <!-- Dark mode init com detecção automática -->
    <script>
        (function () {
            const theme = localStorage.getItem('theme');

            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else if (theme === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white text-black dark:bg-black dark:text-white transition-colors duration-300">

    <!-- NAVBAR -->
    <nav class="flex justify-between items-center px-8 py-6 
                bg-white dark:bg-black 
                border-b border-gray-200 dark:border-gray-800 transition-colors duration-300">

        <div class="flex items-center gap-3">
            <img src="{{ asset('images/logo.png') }}" 
                 alt="Couple Split" 
                 class="w-10">
            <span class="font-semibold text-lg">Couple Split</span>
        </div>

        <div class="flex items-center gap-6 text-sm">

            <!-- Toggle sem emoji -->
            <button onclick="toggleTheme()"
                class="border border-gray-300 dark:border-gray-600
                       p-2 rounded-full
                       hover:bg-gray-100 dark:hover:bg-gray-800
                       transition">

                <!-- Sol -->
                <svg class="w-4 h-4 block dark:hidden"
                     xmlns="http://www.w3.org/2000/svg"
                     fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 3v2m0 14v2m9-9h-2M5 12H3
                           m15.364-6.364l-1.414 1.414
                           M7.05 16.95l-1.414 1.414
                           m0-12.728L7.05 7.05
                           m9.9 9.9l1.414 1.414
                           M12 8a4 4 0 100 8 4 4 0 000-8z" />
                </svg>

                <!-- Lua -->
                <svg class="w-4 h-4 hidden dark:block"
                     xmlns="http://www.w3.org/2000/svg"
                     fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 12.79A9 9 0 1111.21 3
                           7 7 0 0021 12.79z" />
                </svg>

            </button>

            @auth
                <a href="{{ url('/dashboard') }}" class="hover:underline">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="hover:underline">
                    Entrar
                </a>

                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                       class="bg-black text-white 
                              dark:bg-white dark:text-black
                              px-4 py-2 rounded-full transition">
                        Criar Conta
                    </a>
                @endif
            @endauth
        </div>
    </nav>

    <!-- HERO -->
    <section class="flex flex-col lg:flex-row items-center justify-between px-8 lg:px-20 py-20">

        <!-- Texto -->
        <div class="max-w-xl">
            <h1 class="text-4xl lg:text-5xl font-bold leading-tight mb-6">
                Dividir despesas nunca foi tão simples.
            </h1>

            <p class="text-gray-600 dark:text-gray-400 text-lg mb-8">
                Organize gastos do casal, acompanhe quem pagou o quê
                e mantenha o equilíbrio financeiro de forma clara.
            </p>

            @auth
                <a href="{{ url('/dashboard') }}"
                   class="bg-black text-white 
                          dark:bg-white dark:text-black
                          px-8 py-4 rounded-full text-lg transition">
                    Ir para o Dashboard
                </a>
            @else
                <a href="{{ route('register') }}"
                   class="bg-black text-white 
                          dark:bg-white dark:text-black
                          px-8 py-4 rounded-full text-lg transition">
                    Começar Agora
                </a>
            @endauth
        </div>

        <!-- Card mock (cores mantidas) -->
        <div class="mt-16 lg:mt-0">
            <div class="bg-gray-100 dark:bg-gray-900
                        border border-gray-200 dark:border-gray-800
                        shadow-sm rounded-3xl p-8 w-80 transition-colors duration-300">

                <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">
                    Resumo do casal
                </p>

                <p class="text-2xl font-bold mb-4">
                    R$ 2.450,00
                </p>

                <div class="flex justify-between text-sm mb-2">
                    <span>Você pagou</span>
                    <span class="font-semibold text-green-600">
                        R$ 1.300
                    </span>
                </div>

                <div class="flex justify-between text-sm">
                    <span>Parceiro(a)</span>
                    <span class="font-semibold text-red-500">
                        R$ 1.150
                    </span>
                </div>
            </div>
        </div>

    </section>

    <!-- Toggle Script -->
    <script>
        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }
    </script>

</body>
</html>