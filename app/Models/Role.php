<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Role extends Model
{
    use HasUlids;

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::ulid();
            }
        });
    }
}
