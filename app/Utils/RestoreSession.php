<?php

namespace App\Utils;

use Illuminate\Support\Facades\Auth;

class RestoreSession
{
    public static function invoke(): void
    {
        $authPassword = Auth::user()?->getAuthPassword();
        $passwordHashWeb = 'password_hash_web';
        if (request()->session()->get($passwordHashWeb, null) !== null) {
            request()->session()->put([
                $passwordHashWeb => $authPassword,
            ]);
        }
        $passwordHashSanctum = 'password_hash_sanctum';
        if (request()->session()->get($passwordHashSanctum, null) !== null) {
            request()->session()->put([
                $passwordHashSanctum => $authPassword,
            ]);
        }
    }
}
