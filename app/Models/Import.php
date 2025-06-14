<?php

namespace App\Models;

use Filament\Actions\Imports\Models\Import as FilamentImport;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

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
 * @property-read User $user
 *
 * @method static \Database\Factories\ImportFactory factory(...$parameters)
 */
class Import extends FilamentImport
{
    /** @use HasFactory<\Database\Factories\ImportFactory> */
    use HasFactory;

    use HasUlids;
}
