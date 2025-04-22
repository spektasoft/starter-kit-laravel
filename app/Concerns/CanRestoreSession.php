<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Auth;

trait CanRestoreSession
{
    /**
     * Restore the session's password hash after a password change or session logout.
     */
    protected function restoreSession(): void
    {
        $authPassword = Auth::user()?->getAuthPassword();
        $passwordHashWeb = 'password_hash_web';
        if (request()->hasSession() && request()->session()->get($passwordHashWeb, null)
!== null) {
            request()->session()->put([
                $passwordHashWeb => $authPassword,
            ]);
        }
        $passwordHashSanctum = 'password_hash_sanctum';
        if (request()->hasSession() && request()->session()->get($passwordHashSanctum,
            null) !== null) {
            request()->session()->put([
                $passwordHashSanctum => $authPassword,
            ]);
        }
    }
}
