<?php

namespace App\Filament\Livewire;

use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Livewire\DatabaseNotifications as BaseDatabaseNotifications;
use Illuminate\Contracts\View\View;

class DatabaseNotifications extends BaseDatabaseNotifications
{
    public function getTrigger(): ?View
    {
        $position = $this->position ?? filament()->getDatabaseNotificationsPosition();

        if ($position === DatabaseNotificationsPosition::Sidebar) {
            return parent::getTrigger();
        }

        return view('filament.notifications.database-notifications-trigger');
    }
}
