<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Jwt;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticatable;

class LoginController
{
    public function __invoke(Request $request, Jwt $jwt): JsonResponse
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
            $device_name = strip_tags($device_name);

            if (config('fortify.lowercase_usernames')) {
                $username = Str::lower($username);
            }

            $user = User::where(Fortify::username(), $username)->first();

            if (! $user || ! Hash::check($password, $user->password)) {
                throw ValidationException::withMessages([
                    Fortify::username() => ['The provided credentials are incorrect.'],
                ]);
            }

            // Check if a two-factor challenge is required
            $usesTwoFactorAuthenticatable = in_array(TwoFactorAuthenticatable::class, class_uses_recursive($user));
            $hasTwoFactorSecret = ! is_null($user->two_factor_secret);
            $isTwoFactorConfirmed = ! is_null($user->two_factor_confirmed_at);
            $requiresConfirmation = Fortify::confirmsTwoFactorAuthentication();

            if ($usesTwoFactorAuthenticatable && $hasTwoFactorSecret && (! $requiresConfirmation || $isTwoFactorConfirmed)) {
                // Trigger challenge if:
                // - User uses the 2FA trait
                // - User has enabled 2FA (secret exists)
                // - AND (Fortify doesn't require confirmation OR the user has confirmed)
                return $this->twoFactorChallengeResponse($user, $jwt);
            }

            // If no 2FA challenge is required, create and return the token
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

    private function twoFactorChallengeResponse(User $user, Jwt $jwt): JsonResponse
    {
        $payload = [
            'uid' => $user->getKey(),
        ];
        $loginId = $jwt->encode($payload);

        return response()->json([
            'login_id' => $loginId,
            'two_factor' => true,
        ]);
    }
}
