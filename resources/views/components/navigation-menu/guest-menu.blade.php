<x-filament::dropdown placement="bottom-end" teleport>
    <x-slot name="trigger">
        <button aria-label="{{ __('navigation-menu.menu.open_menu') }}" type="button" class="shrink-0">
            <x-filament::icon icon="heroicon-o-ellipsis-vertical" class="w-6 h-6 text-gray-500 dark:text-gray-400" />
        </button>
    </x-slot>

    <x-filament::dropdown.header :icon="'heroicon-o-user-circle'">
        {{ __('navigation-menu.menu.guest') }}
    </x-filament::dropdown.header>

    <x-filament::dropdown.list>
        <x-filament-panels::theme-switcher />
    </x-filament::dropdown.list>

    @if (Route::has('login'))
        <x-filament::dropdown.list.item wire:navigate :href="route('login')" :icon="'heroicon-m-arrow-right-end-on-rectangle'" tag="a">
            @if (Route::has('register'))
                {{ __('navigation-menu.menu.login_register') }}
            @else
                {{ __('navigation-menu.menu.login') }}
            @endif
        </x-filament::dropdown.list.item>
    @endif
</x-filament::dropdown>
