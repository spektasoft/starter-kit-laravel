<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script>
        const theme = localStorage.getItem('theme')

        if (
            theme === 'dark' ||
            (theme === 'system' &&
                window.matchMedia('(prefers-color-scheme: dark)')
                .matches)
        ) {
            document.documentElement.classList.add('dark')
        }
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @filamentStyles
    @vite('resources/css/app.css')
    @livewireStyles
</head>

<body class="font-sans antialiased">
    @auth
        @livewire('navigation-menu')
    @endauth
    <div class="min-h-screen">
        <div class="bg-white dark:bg-gray-900">
            <!-- Page Heading -->
            @if (isset($header))
                <header class="shadow">
                    <div class="px-4 py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="flex flex-col min-h-screen bg-gray-50 dark:bg-gray-950">
                <x-banner />
                <div class="flex-grow">{{ $slot }}</div>
            </main>
        </div>
    </div>

    @stack('modals')

    @filamentScripts(withCore: true)
    @vite('resources/js/app.js')
    @livewireScripts
</body>

</html>
