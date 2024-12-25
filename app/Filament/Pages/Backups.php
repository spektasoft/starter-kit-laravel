<?php

namespace App\Filament\Pages;

use App\Models\User;
use Illuminate\Contracts\Support\Htmlable;
use ShuvroRoy\FilamentSpatieLaravelBackup\Pages\Backups as BaseBackups;

class Backups extends BaseBackups
{
    public static function canAccess(): bool
    {
        $user = User::auth();
        if ($user === null) {
            return false;
        }

        return $user->can('page_Backups');
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament-spatie-backup::backup.pages.backups.navigation.label');
    }
}
