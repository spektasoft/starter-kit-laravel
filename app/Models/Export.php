<?php

namespace App\Models;

use Filament\Actions\Exports\Models\Export as FilamentExport;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * @property string $user_id
 * @property ?Carbon $completed_at
 * @property string $file_disk
 * @property string|null $file_name
 * @property string $exporter
 * @property int $processed_rows
 * @property int $total_rows
 * @property int $successful_rows
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read User $user
 *
 * @method static \Database\Factories\ExportFactory factory(...$parameters)
 */
class Export extends FilamentExport
{
    /** @use HasFactory<\Database\Factories\ExportFactory> */
    use HasFactory;

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
