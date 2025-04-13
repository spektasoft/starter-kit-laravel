@php
    $user = filament()->auth()->user();
    $items = filament()->getUserMenuItems();
    $profileItem = $items['profile'] ?? ($items['account'] ?? null);
    $logoutItem = $items['logout'] ?? null;
@endphp

<x-filament::dropdown placement="bottom-end" teleport>
    <x-slot name="trigger">
        <button aria-label="{{ __('navigation-menu.menu.open_menu') }}" type="button" class="shrink-0">
            <x-filament-panels::avatar.user :user="$user" />
        </button>
    </x-slot>

    <x-filament::dropdown.header :icon="'heroicon-m-user-circle'">
        {{ $user->name }}
    </x-filament::dropdown.header>

    <x-filament::dropdown.list>
        <x-filament-panels::theme-switcher />
    </x-filament::dropdown.list>

    <x-filament::dropdown.list>
        <x-filament::dropdown.list.item :href="route('profile.show')" :icon="'heroicon-o-user'" tag="a">
            {{ __('navigation-menu.menu.profile') }}
        </x-filament::dropdown.list.item>

        @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
            <x-filament::dropdown.list.item :href="route('api-tokens.index')" :icon="'heroicon-o-key'" tag="a">
                {{ __('navigation-menu.menu.api_tokens') }}
            </x-filament::dropdown.list.item>
        @endif

        <x-filament::dropdown.list.item :action="route('logout')" :icon="'heroicon-o-arrow-left-on-rectangle'" method="post" tag="form">
            {{ $logoutItem?->getLabel() ?? __('filament-panels::layout.actions.logout.label') }}
        </x-filament::dropdown.list.item>
    </x-filament::dropdown.list>
</x-filament::dropdown>
