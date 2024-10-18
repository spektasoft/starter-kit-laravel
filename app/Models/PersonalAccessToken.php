<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersoalAccessToken;

class PersonalAccessToken extends SanctumPersoalAccessToken
{
    use HasUlids;
}
