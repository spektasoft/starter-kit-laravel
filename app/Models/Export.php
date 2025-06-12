<?php

namespace App\Models;

use Filament\Actions\Exports\Models\Export as FilamentExport;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Export extends FilamentExport
{
    use HasUlids;
}
