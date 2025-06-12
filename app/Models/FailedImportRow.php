<?php

namespace App\Models;

use Filament\Actions\Imports\Models\FailedImportRow as FilamentFailedImportRow;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class FailedImportRow extends FilamentFailedImportRow
{
    use HasUlids;
}
