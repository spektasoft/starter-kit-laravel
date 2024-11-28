<?php

namespace App\Http\Controllers\Api\V1;

use Ahc\Jwt\JWTException;
use App\Contracts\Jwt;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Fortify;

class TwoFactorChallengeController
{
    public function __invoke(Request $request, Jwt $jwt): JsonResponse
    {
        try {
            $request->validate([
                'login_id' => 'required',
            ]);

            /** @var string */
            $loginId = $request->login_id;
            /** @var string */
            $code = $request->code ?? '';
            /** @var string */
            $recoveryCode = $request->recovery_code ?? '';
            /** @var string */
            $deviceName = $request->device_name;

            /** @var string|null */
            $id = $jwt->decode($loginId)['uid'];
            $user = User::find($id);

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
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        } catch (JWTException $e) {
            return response()->json([
                'errors' => $e->getMessage(),
            ], JsonResponse::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json([
                'errors' => ['An unexpected error occurred.'],
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
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
        /** @var string[] */
        $recoveryCodes = $user->recoveryCodes();
        /** @var ?string */
        $code = collect($recoveryCodes)->first(function ($code) use ($recoveryCode) {
            return hash_equals($code, $recoveryCode);
        });

        if ($code === null) {
            return false;
        }

        $user->replaceRecoveryCode($code);

        event(new RecoveryCodeReplaced($user, $code));

        return true;
    }
}