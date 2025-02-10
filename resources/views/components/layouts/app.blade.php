<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    @googlefonts('sans')

    <!-- Styles -->
    @filamentStyles
    @vite(['resources/css/app.css'])
    @livewireStyles
</head>

<body class="font-sans antialiased text-black bg-white dark:text-white dark:bg-gray-900">
    @livewire('navigation-menu')
    <div class="pt-16">
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

    <x-curator::modals.modal />

    @stack('modals')
    @livewire('notifications')

    @filamentScripts(withCore: true)
    @vite(['resources/ts/app.ts'])
    @livewireScripts

    <!-- Scripts -->
    <script>
        var theme = localStorage.getItem('theme')

        if (
            theme === 'dark' ||
            (theme === 'system' &&
                window.matchMedia('(prefers-color-scheme: dark)')
                .matches)
        ) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</body>

</html>
