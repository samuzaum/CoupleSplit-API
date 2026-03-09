<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Couple Split') }}</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    <script>
        if (localStorage.getItem('theme') === 'dark') {
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

        <!-- Sua Logo -->
        <div class="mb-10">
            <a href="/">
                <img src="{{ asset('images/logo.png') }}" 
                     alt="Couple Split"
                     class="h-12 mx-auto">
            </a>
        </div>
        <!-- Container minimalista -->
        <div class="w-full max-w-sm">
            {{ $slot }}
        </div>

    </div>

</body>
</html>