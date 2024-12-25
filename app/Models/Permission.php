<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasUlids;

    public static function boot()
    {
        parent::boot();

        static::firstOrCreate(['name' => 'download-backup']);
        static::firstOrCreate(['name' => 'delete-backup']);
    }

    public function isReferenced(): bool
    {
        if ($this->roles()->exists()) {
            return true;
        }

        return false;
    }
}
