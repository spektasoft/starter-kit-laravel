<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * @property User $user
 */
trait HasUser
{
    protected ?User $memoizedUser = null;

    public function getUserProperty(): User
    {
        if ($this->memoizedUser) {
            return $this->memoizedUser;
        }

        $user = Auth::user();

        if (! ($user instanceof User)) {
            abort(403);
        }

        return $this->memoizedUser = $user;
    }
}
