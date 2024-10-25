<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     *
     * @param  array<string, string>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ])->validateWithBag('updatePassword');

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();

        if (request()->hasSession()) {
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
}
