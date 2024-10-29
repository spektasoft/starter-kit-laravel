<x-filament::dropdown placement="bottom-end" teleport>
    <x-slot name="trigger">
        <button aria-label="{{ __('navigation-menu.language_switcher.open_language_switcher') }}" type="button"
            class="flex flex-row items-center justify-center gap-2 text-sm shrink-0">
            <x-filament::icon icon="heroicon-o-language" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
            <div class="hidden text-gray-500 sm:flex dark:text-gray-400">
                {{ __('navigation-menu.language_switcher.language') }}
            </div>
            <x-filament::icon icon="heroicon-o-chevron-down" class="w-3 h-3 text-gray-500 dark:text-gray-400" />
        </button>
    </x-slot>
    <x-filament::dropdown.list>
        <x-filament::dropdown.list.item href="{{ request()->fullUrlWithQuery(['lang' => 'id']) }}" tag="a">
            Bahasa Indonesia
        </x-filament::dropdown.list.item>
        <x-filament::dropdown.list.item href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" tag="a">
            English
        </x-filament::dropdown.list.item>
    </x-filament::dropdown.list>
</x-filament::dropdown>
