<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * @property User $user
 */
trait HasUser
{
    public function getUserProperty(): User
    {
        $user = Auth::user();

        if (! ($user instanceof User)) {
            abort(403);
        }

        return $user;
    }
}
