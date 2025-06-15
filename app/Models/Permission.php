<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasUlids;

    /**
     * @var string[]
     */
    public static $customPermissions = [
        'delete-backup',
        'download-backup',
    ];

    public function isReferenced(): bool
    {
        if ($this->roles()->exists()) {
            return true;
        }

        return false;
    }
}
