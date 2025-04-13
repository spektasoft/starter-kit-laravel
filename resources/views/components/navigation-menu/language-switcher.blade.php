@php
    $currentLocale = app()->getLocale();
@endphp

<x-filament::dropdown placement="bottom-end" teleport>
    <x-slot name="trigger">
        <button aria-label="{{ __('navigation-menu.language_switcher.open_language_switcher') }}" type="button"
            class="flex flex-row items-center justify-center gap-2 text-sm shrink-0 p-2.5 hover:bg-gray-500/10 rounded-2xl hover:dark:bg-gray-400/10">
            <x-filament::icon icon="heroicon-m-language" color="gray" class="size-6" />
            <div class="hidden text-gray-500 sm:flex dark:text-gray-400">
                {{ __('navigation-menu.language_switcher.language') }}
            </div>
            <x-filament::icon icon="heroicon-m-chevron-down" color="gray" class="size-3" />
        </button>
    </x-slot>
    <x-filament::dropdown.list class="space-y-1" x-data="languageSwitcher" x-init="switchLanguage('{{ $currentLocale }}')">
        <x-navigation-menu.language-switcher-item locale="id" languageName="Bahasa Indonesia" :currentLocale="$currentLocale"
            x-on:click="switchLanguage('id')" />
        <x-navigation-menu.language-switcher-item locale="en" languageName="English" :currentLocale="$currentLocale"
            x-on:click="switchLanguage('en')" />
    </x-filament::dropdown.list>
</x-filament::dropdown>
