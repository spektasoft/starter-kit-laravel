<x-filament::dropdown placement="bottom-end" teleport>
    <x-slot name="trigger">
        <x-filament::icon-button color="gray" class="p-2.5 hover:bg-gray-500/10 rounded-2xl hover:dark:bg-gray-400/10"
            icon="heroicon-m-ellipsis-vertical" :label="__('navigation-menu.menu.open_menu')" size="xl" />
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
