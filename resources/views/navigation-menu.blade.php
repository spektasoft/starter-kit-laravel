<nav x-data="{ open: false, toggle() { this.open = !this.open; } }"
    class="fixed left-0 z-10 w-full duration-500 bg-white dark:bg-gray-900 dark:border-gray-950/5 transition-top">
    <!-- Primary Navigation Menu -->
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Hamburger -->
                <div class="flex items-center">
                    <button title="Menu" x-on:click="toggle()" x-on:click.outside="open = false"
                        class="mr-2 p-2.5 text-gray-500 rounded-lg dark:text-gray-400">
                        <template x-if="!open">
                            @svg('heroicon-o-bars-3', 'w-5 h-5')
                        </template>
                        <template x-if="open">
                            @svg('heroicon-o-x-mark', 'w-5 h-5')
                        </template>
                    </button>
                </div>

                <!-- Logo -->
                <div class="flex items-center shrink-0">
                    <a wire:navigate href="{{ route('home') }}">
                        <x-application-mark class="block w-auto h-9" />
                    </a>
                </div>
            </div>

            <div class="flex flex-row items-center space-x-4">
                <!-- Language Switcher -->
                <div class="relative">
                    <x-navigation-menu.language-switcher />
                </div>

                <!-- Menu -->
                <div class="flex items-center gap-4">
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

    <!-- Drawer -->
    <div id="drawer-navigation" :class="{ '-translate-x-0': open, '-translate-x-full': !open }"
        class="fixed left-0 z-40 w-64 h-screen p-4 overflow-y-auto transition-all duration-500 -translate-x-full bg-white top-16 dark:bg-gray-900"
        tabindex="-1" aria-labelledby="drawer-navigation-label">
        <div class="overflow-y-auto">
            <div class="space-y-2 font-medium">
                <x-nav-link wire:navigate href="{{ route('home') }}" :active="request()->routeIs('home')" icon="heroicon-o-home">
                    <span class="flex items-center gap-2">
                        {{ __('navigation-menu.menu.home') }}
                    </span>
                </x-nav-link>
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
