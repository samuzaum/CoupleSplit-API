<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'CoupleSplit') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo-branca.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <script>
            if (localStorage.getItem('theme') === 'dark' ||
                (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-black dark:text-white">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="pt-10 sm:pb-0" style="padding-bottom: env(safe-area-inset-bottom)">
                {{ $slot }}
            </main>
        </div>
    <script>
        var LOGO_LIGHT = '{{ asset("images/logo-preta.png") }}';
        var LOGO_DARK  = '{{ asset("images/logo-branca.png") }}';

        function applyLogo() {
            var logo = document.getElementById('nav-logo');
            if (!logo) return;
            logo.src = document.documentElement.classList.contains('dark') ? LOGO_DARK : LOGO_LIGHT;
        }

        function toggleTheme() {
            var html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
            applyLogo();
        }

        // aplica logo correta ao carregar (o tema já foi setado no <head>)
        document.addEventListener('DOMContentLoaded', applyLogo);
    </script>
    </body>
</html>