<?php

namespace App\Models;

use Filament\Actions\Exports\Models\Export as FilamentExport;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * @property-read User $user
 */
class Export extends FilamentExport
{
    use HasUlids;

    protected static function booted(): void
    {
        parent::booted();

        static::deleted(function (Export $export) {
            $disk = $export->getFileDisk();
            $directory = $export->getFileDirectory();

            if ($disk->exists($directory)) {
                $disk->deleteDirectory($directory);
            }
        });
    }
}
