<x-layouts.app>
    <x-container>
        <div class="flex flex-col items-center justify-center min-h-[50vh]">
            <div class="flex items-center pt-8 sm:justify-start sm:pt-0">
                <div class="px-4 text-4xl font-bold tracking-wider border-r border-gray-400">
                    @yield('code')
                </div>

                <div class="ml-4 text-lg tracking-wider text-gray-500 uppercase">
                    @yield('message')
                </div>
            </div>
            <div class="mt-8">
                <x-filament::button :href="route('home')" tag="a" wire:navigate icon="heroicon-o-home">
                    <span>{{ __('navigation-menu.menu.home') }}</span>
                </x-filament::button>
            </div>
        </div>
    </x-container>
</x-layouts.app>
