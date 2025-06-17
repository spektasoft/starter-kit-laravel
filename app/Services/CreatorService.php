<?php

namespace App\Services;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class CreatorService
{
    /**
     * The user who is acting as the creator for the current operation.
     */
    protected static ?User $creator = null;

    /**
     * Set the creator for a specific block of code.
     * Ensures the creator is cleared afterwards to prevent state leakage.
     *
     * @return mixed
     */
    public static function withCreator(User $user, Closure $callback)
    {
        static::$creator = $user;

        try {
            // Execute the provided logic
            return $callback();
        } finally {
            // Always clear the creator after the operation is complete
            static::$creator = null;
        }
    }

    /**
     * Get the currently set creator.
     */
    public static function getCreator(): ?User
    {
        // Return the explicitly set creator, or fall back to the authenticated user.
        return static::$creator ?? (Auth::user() instanceof User ? Auth::user() : null);
    }

    /**
     * Get the currently set creator or fail if none is found.
     * This is the method our models will use.
     *
     * @throws RuntimeException
     */
    public static function getCreatorOrFail(): User
    {
        $creator = self::getCreator();

        if (! $creator) {
            throw new RuntimeException(
                'A creator could not be determined. Ensure the operation is run by an authenticated user or within a CreatorService::withCreator() block.'
            );
        }

        return $creator;
    }
}
