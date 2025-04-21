<?php

namespace App\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests as BaseAuthorizesRequests;
use Laravel\Sanctum\Contracts\HasAbilities;

trait AuthorizesRequestsWithTokens
{
    use BaseAuthorizesRequests;

    /**
     * Authorize the current user's token for a given permission.
     *
     * Requires an authenticated user with a current access token
     * that possesses the specified ability.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function authorizeToken(string $tokenPermission): void
    {
        // Use standard Laravel helper to get the authenticated user
        $user = request()->user();

        /** @var HasAbilities|null */
        $token = $user?->currentAccessToken();

        // Check if user exists, token exists, and token has the ability
        if (! $user || ! $token || ! $user->tokenCan($tokenPermission)) {
            throw new AuthorizationException;
        }
    }
}
