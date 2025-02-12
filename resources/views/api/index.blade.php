<x-app-layout>
    <x-header>
        {{ __('API Tokens') }}
    </x-header>

    <div>
        <div class="py-10 mx-auto max-w-7xl sm:px-6 lg:px-8">
            @livewire('api.api-token-manager')
        </div>
    </div>
</x-app-layout>
