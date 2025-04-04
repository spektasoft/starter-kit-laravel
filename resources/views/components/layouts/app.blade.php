<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" dir="{{ __('layout.direction') ?? 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {!! \Artesaos\SEOTools\Facades\SEOTools::generate() !!}

    <!-- Fonts -->
    @googlefonts('sans')

    <!-- Styles -->
    @filamentStyles
    @vite(['resources/css/app.css'])
    @livewireStyles

    <!-- Scripts -->
    @vite('resources/ts/app.ts')
</head>

<body class="font-sans antialiased text-black bg-white dark:text-white dark:bg-gray-900">
    @livewire('navigation-menu')

    <main class="flex flex-col min-h-screen bg-gray-50 dark:bg-gray-950">
        <x-banner />
        <div class="flex-grow">{{ $slot }}</div>
    </main>

    <x-curator::modals.modal />

    @stack('modals')
    @livewire('notifications')

    @filamentScripts(withCore: true)
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
