<?php

namespace App\Models;

use Filament\Actions\Imports\Models\Import as FilamentImport;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * @property-read User $user
 */
class Import extends FilamentImport
{
    use HasUlids;
}
