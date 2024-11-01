<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Fortify;

class TwoFactorChallengeController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $request->validate([
                Fortify::username() => 'required',
            ]);

            /** @var string */
            $username = $request->{Fortify::username()};
            /** @var string */
            $code = $request->code;
            /** @var string */
            $recoveryCode = $request->recovery_code;
            /** @var string */
            $deviceName = $request->device_name;

            if (config('fortify.lowercase_usernames')) {
                $username = Str::lower($username);
            }

            $user = User::where(Fortify::username(), $username)->first();

            if (! $user) {
                throw ValidationException::withMessages([
                    Fortify::username() => ['The provided credentials are incorrect.'],
                ]);
            }

            if ($code !== '' && $this->hasValidCode($user, $code)) {
                return $this->createTokenResponse($user, $deviceName);
            } elseif ($recoveryCode !== '' && $this->verifyAndReplaceValidRecoveryCode($user, $recoveryCode)) {
                return $this->createTokenResponse($user, $deviceName);
            }

            throw ValidationException::withMessages([
                'code' => ['The provided credentials are incorrect.'],
                'recovery_code' => ['The provided credentials are incorrect.'],
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

    private function createTokenResponse(User $user, string $deviceName): JsonResponse
    {
        $token = $user->createToken($deviceName, ['*'])->plainTextToken;

        return response()->json([
            'token' => $token,
        ]);
    }

    private function hasValidCode(User $user, string $code): bool
    {
        /** @var string */
        $twoFactorSecret = $user->two_factor_secret;
        /** @var string */
        $secret = decrypt($twoFactorSecret);

        return app(TwoFactorAuthenticationProvider::class)->verify($secret, $code);
    }

    private function verifyAndReplaceValidRecoveryCode(User $user, string $recoveryCode): bool
    {
        /** @var string | null */
        $code = collect($user->recoveryCodes())->first(function ($code) use ($recoveryCode) {
            return hash_equals($code, $recoveryCode) ? $code : null;
        });

        if ($code === null) {
            return false;
        }

        $user->replaceRecoveryCode($code);

        event(new RecoveryCodeReplaced($user, $code));

        return true;
    }
}
