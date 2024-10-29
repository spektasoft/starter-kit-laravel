<x-authentication-card>
    <x-slot name="logo">
        <x-authentication-card-logo />
    </x-slot>

    @if (session('status') == 'verification-link-sent')
        <x-filament::section class="mb-4">
            <div class="text-sm font-medium text-green-600 dark:text-green-400">
                {{ __('A new verification link has been sent to the email address you provided in your profile settings.') }}
            </div>
        </x-filament::section>
    @endif

    <x-form action="{{ route('verification.send') }}">
        @csrf

        {{ $this->form }}
    </x-form>
</x-authentication-card>
