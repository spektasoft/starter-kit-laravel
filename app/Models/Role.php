<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasUlids;

    public function isReferenced(): bool
    {
        if ($this->users()->exists()) {
            return true;
        }

        return false;
    }
}
