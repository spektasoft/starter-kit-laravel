<?php

namespace App\Models;

use Filament\Actions\Imports\Models\Import as FilamentImport;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Import extends FilamentImport
{
    use HasUlids;
}
