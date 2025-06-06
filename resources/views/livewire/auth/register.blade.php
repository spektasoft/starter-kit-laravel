<x-authentication-card>
    <x-slot name="logo">
        <x-authentication-card-logo />
    </x-slot>

    <x-validation-errors class="mb-4" />

    <x-status class="mb-4" />

    <x-form action="{{ route('register') }}">
        @csrf
        <x-honeypot />
        {{ $this->form }}
    </x-form>
</x-authentication-card>
