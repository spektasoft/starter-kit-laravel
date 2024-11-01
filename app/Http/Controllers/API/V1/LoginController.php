<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticatable;

class LoginController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $request->validate([
                Fortify::username() => 'required',
                'password' => 'required',
                'device_name' => 'required',
            ]);

            /** @var string */
            $username = $request->{Fortify::username()};
            /** @var string */
            $password = $request->password;
            /** @var string */
            $device_name = $request->device_name;

            if (config('fortify.lowercase_usernames')) {
                $username = Str::lower($username);
            }

            $user = User::where(Fortify::username(), $username)->first();

            if (! $user || ! Hash::check($password, $user->password)) {
                throw ValidationException::withMessages([
                    Fortify::username() => ['The provided credentials are incorrect.'],
                ]);
            }

            if (Features::enabled(Features::twoFactorAuthentication())) {
                if ($user->two_factor_secret &&
                ! is_null($user->two_factor_confirmed_at) &&
                in_array(TwoFactorAuthenticatable::class, class_uses_recursive($user))) {
                    return response()->json(['two_factor' => true]);
                }
            }

            $token = $user->createToken($device_name, ['*'])->plainTextToken;

            return response()->json([
                'token' => $token,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => ['An unexpected error occurred.'],
            ], 500);
        }
    }
}
