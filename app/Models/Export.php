<?php

namespace App\Models;

use App\Services\CreatorService;
use Filament\Actions\Exports\Models\Export as FilamentExport;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property string $creator_id
 * @property-read User $creator
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

        static::creating(function (Export $export) {
            // If creator_id is not already set, assign it.
            // This prevents overriding a manually set ID (e.g., in tests).
            // @phpstan-ignore-next-line
            if (is_null($export->creator_id)) {
                $export->creator_id = CreatorService::getCreatorOrFail()->id;
            }
        });

        static::deleted(function (Export $export) {
            $disk = $export->getFileDisk();
            $directory = $export->getFileDirectory();

            if ($disk->exists($directory)) {
                $disk->deleteDirectory($directory);
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
