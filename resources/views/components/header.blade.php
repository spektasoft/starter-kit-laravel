@props([
    'actions' => [],
    'breadcrumbs' => [],
])

<div x-data="header" x-on:scroll.window.throttle.100ms="scroll"
    :class="{
        '-translate-y-0 top-16': show,
        '-translate-y-full top-0': !show
    }"
    class="sticky z-10 duration-500 bg-white transition-top dark:bg-gray-900">
    <section {{ $attributes->merge(['class' => 'mx-auto max-w-7xl']) }}>
        <header class="flex flex-col px-4 pb-4 sm:px-6 lg:px-8 fi-header sm:flex-row sm:items-center sm:justify-between">
            <div>
                @if ($breadcrumbs)
                    <x-filament::breadcrumbs :breadcrumbs="$breadcrumbs" class="hidden mb-2 sm:block" />
                @else
                    <div class="hidden mb-2 sm:block"></div>
                @endif
                <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                    {{ $slot }}
                </h1>
            </div>
            <div>
                @if ($actions)
                    <x-filament::actions :actions="$actions" />
                @endif
            </div>
        </header>
    </section>
</div>
