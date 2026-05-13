<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CoupleSplit') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-branca.png') }}">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    <script>
        if (localStorage.getItem('theme') === 'dark' ||
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-['Instrument_Sans'] antialiased
             bg-gray-50 dark:bg-black
             text-black dark:text-white
             transition-colors duration-200">

    <div class="min-h-screen flex flex-col items-center justify-center px-6">

        <!-- Logo (tema sincronizado) -->
        <div class="mb-10">
            <a href="/">
                <img src="{{ asset('images/logo-preta.png') }}"
                     alt="CoupleSplit"
                     class="h-12 mx-auto dark:hidden">
                <img src="{{ asset('images/logo-branca.png') }}"
                     alt="CoupleSplit"
                     class="h-12 mx-auto hidden dark:block">
            </a>
        </div>
        <!-- Container minimalista -->
        <div class="w-full max-w-sm">
            {{ $slot }}
        </div>

    </div>

</body>
</html>