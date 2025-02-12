<x-app-layout>
    <x-header>
        {{ __('Profile') }}
    </x-header>

    <div>
        <div class="py-10 mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                @livewire('edit-profile.update-profile-information-form')

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                @livewire('edit-profile.update-password-form')

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                @livewire('edit-profile.two-factor-authentication-form')

                <x-section-border />
            @endif

            @livewire('edit-profile.logout-other-browser-sessions-form')

            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                <x-section-border />

                @livewire('edit-profile.delete-user-form')
            @endif
        </div>
    </div>
</x-app-layout>
