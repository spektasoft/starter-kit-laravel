<?php

namespace App\Utils;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Contracts\HasAbilities;

class Authorizer
{
    public static function authorize(string $action, Model|string $model): void
    {
        $user = static::getUser();

        /** @var object|string|null */
        $policy = Gate::getPolicyFor($model);

        if (
            ($policy === null) ||
            (! method_exists($policy, $action))
        ) {
            throw new AuthorizationException;
        }

        Gate::forUser($user)->authorize($action, $model);
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

    public static function check(string $action, Model|string $model): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        /** @var object|string|null */
        $policy = Gate::getPolicyFor($model);

        if (
            ($policy === null) ||
            (! method_exists($policy, $action))
        ) {
            return false;
        }

        return Gate::forUser($user)->check($action, $model);
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
