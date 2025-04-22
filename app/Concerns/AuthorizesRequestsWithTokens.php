<?php

namespace App\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Contracts\HasAbilities;

trait AuthorizesRequestsWithTokens
{
    /**
     * Authorize the current user's token for a given permission.
     *
     * Requires an authenticated user with a current access token.
     *
     * @return bool True if token exists and has the ability, false otherwise.
     */
    protected function checkTokenAbility(string $tokenPermission): bool
    {
        // Use standard Laravel helper to get the authenticated user
        $user = request()->user();

        /** @var HasAbilities|null */
        $token = $user?->currentAccessToken();

        // Check if user exists, token exists, and token has the ability
        return $user && $token && $user->tokenCan($tokenPermission);
    }

    /**
     * Authorize the current user based on either token ability or policy.
     *
     * Authorization passes if the user's token has the specified ability
     * OR if the user is authorized by the specified policy.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException If neither check passes.
     */
    protected function authorizeTokenOrPolicy(string $tokenPermission, string $policyAbility, mixed $policyArguments = []): void
    {
        // Check token ability
        $tokenAuthorized = $this->checkTokenAbility($tokenPermission);

        // Check policy authorization
        $user = request()->user();
        $policyAuthorized = false;
        if ($user) {
            // Use Gate::allows to check policy without throwing an exception immediately
            $policyAuthorized = Gate::forUser($user)->allows($policyAbility, $policyArguments);
        }

        // If neither is authorized, throw an exception
        if (! $tokenAuthorized && ! $policyAuthorized) {
            // Throw a standard AuthorizationException
            throw new AuthorizationException;
        }
    }
}
