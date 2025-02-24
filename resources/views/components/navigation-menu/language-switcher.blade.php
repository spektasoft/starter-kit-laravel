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
    <x-filament::dropdown.list x-data="languageSwitcher" x-init="switchLanguage('{{ app()->getLocale() }}')">
        <x-filament::dropdown.list.item x-on:click="switchLanguage('id')"
            href="{{ request()->fullUrlWithQuery(['lang' => 'id']) }}" tag="a">
            Bahasa Indonesia
        </x-filament::dropdown.list.item>
        <x-filament::dropdown.list.item x-on:click="switchLanguage('en')"
            href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" tag="a">
            English
        </x-filament::dropdown.list.item>
    </x-filament::dropdown.list>
</x-filament::dropdown>
