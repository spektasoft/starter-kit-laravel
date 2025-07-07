<nav x-data="navigationMenu" x-on:scroll.window.throttle.100ms="scroll"
    :class="{
        '': show,
        '-translate-y-full': !show
    }"
    class="sticky top-0 left-0 z-20 w-full duration-500 bg-white transition-top dark:bg-gray-900 dark:border-gray-950/5">

    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex flex-row gap-2">
                <!-- Hamburger -->
                <div class="flex items-center">
                    <div title="Menu" x-on:click="toggle()" x-on:click.outside="open = false"
                        class="p-2.5 hover:bg-gray-500/10 rounded-2xl hover:dark:bg-gray-400/10">
                        <template x-if="!open">
                            <x-filament::icon-button icon="heroicon-m-bars-3" color="gray" size="xl" />
                        </template>
                        <template x-if="open">
                            <x-filament::icon-button icon="heroicon-m-x-mark" color="gray" size="xl" />
                        </template>
                    </div>
                </div>

                <!-- Logo -->
                <div class="flex items-center shrink-0">
                    <a wire:navigate href="{{ route('home') }}">
                        <x-app-logo-icon class="text-black fill-current size-9 dark:text-white" />
                    </a>
                </div>
            </div>

            <div class="flex flex-row items-center gap-2">
                @auth
                    <!-- Notifications Trigger -->
                    <div class="relative">
                        @livewire('database-notifications')
                    </div>
                @endauth

                <!-- Language Switcher -->
                <div class="relative">
                    <x-navigation-menu.language-switcher />
                </div>

                <!-- Menu -->
                <div>
                    @guest
                        <x-navigation-menu.guest-menu />
                    @endguest
                    @auth
                        <x-navigation-menu.user-menu />
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <!-- Drawer Overlay -->
    <div :class="{
        '': open,
        '-translate-x-full': !open
    }"
        class="fixed left-0 z-30 w-full h-screen p-4 overflow-y-auto -translate-x-full bg-gray-950/50 dark:bg-gray-950/75 top-16">
    </div>

    <!-- Drawer -->
    <div :class="{
        '': open,
        '-translate-x-full': !open
    }"
        class="fixed left-0 z-40 w-64 h-screen p-4 overflow-y-auto transition-transform -translate-x-full bg-white top-16 dark:bg-gray-900"
        tabindex="-1" aria-labelledby="drawer-navigation-label">
        <div class="overflow-y-auto">
            <div class="space-y-1 font-medium">
                <x-nav-link wire:navigate href="{{ route('home') }}" :active="request()->routeIs('home')" icon="heroicon-o-home">
                    <span class="flex items-center gap-2">
                        {{ __('navigation-menu.menu.home') }}
                    </span>
                </x-nav-link>
                @auth
                    <x-nav-link wire:navigate href="{{ \Filament\Pages\Dashboard::getUrl() }}" :icon="\Filament\Support\Facades\FilamentIcon::resolve(
                        'panels::pages.dashboard.navigation-item',
                    )">
                        <span class="flex items-center gap-2">
                            {{ __('filament-panels::pages/dashboard.title') }}
                        </span>
                    </x-nav-link>
                @endauth
                @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                    <x-menu-border />
                    <x-nav-link wire:navigate href="{{ route('terms.show') }}" :active="request()->routeIs('terms.show')"
                        icon="heroicon-o-scale">
                        <span class="flex items-center gap-2">
                            {{ __('Terms of Service') }}
                        </span>
                    </x-nav-link>
                    <x-nav-link wire:navigate href="{{ route('policy.show') }}" :active="request()->routeIs('policy.show')"
                        icon="heroicon-o-finger-print">
                        <span class="flex items-center gap-2">
                            {{ __('Privacy Policy') }}
                        </span>
                    </x-nav-link>
                @endif
            </div>
        </div>
    </div>
</nav>
