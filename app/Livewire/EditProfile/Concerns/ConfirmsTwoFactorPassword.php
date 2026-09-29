<?php

namespace App\Livewire\EditProfile\Concerns;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

trait ConfirmsTwoFactorPassword
{
    protected function confirmPassword(?string $password): void
    {
        if (! $this->requiresPasswordConfirmation()) {
            return;
        }

        if (! $password || ! Hash::check($password, $this->user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('This password does not match our records.')],
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);
    }

    protected function requiresPasswordConfirmation(): bool
    {
        if (! Fortify::confirmsTwoFactorAuthentication()) {
            return false;
        }

        $confirmedAt = session('auth.password_confirmed_at', 0);
        $timeout = config('auth.password_timeout', 10800);

        $elapsed = time() - (is_numeric($confirmedAt) ? (int) $confirmedAt : 0);
        $limit = is_numeric($timeout) ? (int) $timeout : 10800;

        return $elapsed >= $limit;
    }
}
