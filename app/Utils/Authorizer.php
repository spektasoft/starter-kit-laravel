<?php

namespace App\Utils;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\Contracts\HasAbilities;

class Authorizer
{
    public static function authorize(string $permission, Model|string $model): void
    {
        $user = static::getUser();

        if ($user->cannot($permission, $model)) {
            throw new AuthorizationException;
        }
    }

    public static function authorizeToken(string $tokenPermission): void
    {
        $user = static::getUser();

        /** @var HasAbilities|null */
        $token = $user->currentAccessToken();

        if ($token) {
            if (! $user->tokenCan($tokenPermission)) {
                throw new AuthorizationException;
            }
        }
    }

    public static function getUser(): User
    {
        $user = User::auth();

        if (! $user) {
            throw new AuthorizationException;
        }

        return $user;
    }
}
