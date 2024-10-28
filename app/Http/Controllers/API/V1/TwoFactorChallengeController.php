<?php

namespace App\Http\Controllers\API\V1;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Events\RecoveryCodeReplaced;

class TwoFactorChallengeController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            /** @var string */
            $email = $request->email;
            /** @var string */
            $code = $request->code;
            /** @var string */
            $recoveryCode = $request->recovery_code;
            /** @var string */
            $deviceName = $request->device_name;

            $user = User::where('email', $email)->first();

            if (! $user) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
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

        return tap(app(TwoFactorAuthenticationProvider::class)->verify($secret, $code), function ($result) {
            return $result;
        });
    }

    private function verifyAndReplaceValidRecoveryCode(User $user, string $recoveryCode): bool
    {
        /** @var string | null */
        $code = tap(collect($user->recoveryCodes())->first(function ($code) use ($recoveryCode) {
            return hash_equals($code, $recoveryCode) ? $code : null;
        }), function ($code) {
            return $code;
        });

        if ($code === null) {
            return false;
        }

        $user->replaceRecoveryCode($code);

        event(new RecoveryCodeReplaced($user, $code));

        return true;
    }
}
