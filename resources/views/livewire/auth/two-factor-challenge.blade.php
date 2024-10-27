<x-authentication-card>
    <x-slot name="logo">
        <x-authentication-card-logo />
    </x-slot>

    <x-form action="{{ route('two-factor.login') }}">
        @csrf

        {{ $this->form }}
    </x-form>
</x-authentication-card>
