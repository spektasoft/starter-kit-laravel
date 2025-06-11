<x-filament::icon-button size="lg" color="gray" icon="heroicon-o-bell" label="Mark notifications as read">
    <x-slot name="badge">
        @if ($unreadNotificationsCount > 0)
            {{ $unreadNotificationsCount }}
        @endif
    </x-slot>
</x-filament::icon-button>
