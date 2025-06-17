<?php

namespace App\Models;

use Filament\Actions\Imports\Models\Import as FilamentImport;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @property string $id
 * @property ?Carbon $completed_at
 * @property string|null $file_name
 * @property string $file_path
 * @property string $importer
 * @property int $processed_rows
 * @property int $total_rows
 * @property int $successful_rows
 * @property string $user_id
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property string $creator_id
 * @property-read User $creator
 * @property-read User $user
 *
 * @method static \Database\Factories\ImportFactory factory(...$parameters)
 */
class Import extends FilamentImport
{
    /** @use HasFactory<\Database\Factories\ImportFactory> */
    use HasFactory;

    use HasUlids;

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Import $import) {
            /** @var ?string */
            $creatorId = Auth::id();
            if ($creatorId === null) {
                return false;
            }
            $import->creator_id = $creatorId;
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
