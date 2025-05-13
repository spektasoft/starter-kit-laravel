<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Contracts\Jwt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\RecoveryCode;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorChallengeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_expires_after_one_hour(): void
    {
        $loginId = $this->getTwoFactorAuthenticationLoginId();
        $jwt = app(Jwt::class);

        $jwt->setTestTimestamp(time() + 3600);

        $response = $this->postJson(route('api.v1.two-factor-challenge'), [
            'login_id' => $loginId,
            'code' => '123456',
            'recovery_code' => '',
            'device_name' => 'device_name',
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNAUTHORIZED);
    }

    public function test_failed_two_factor_authentication(): void
    {
        $loginId = $this->getTwoFactorAuthenticationLoginId();

        $response = $this->postJson(route('api.v1.two-factor-challenge'), [
            'login_id' => $loginId,
            'code' => '123456',
            'recovery_code' => '',
            'device_name' => 'device_name',
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_failed_recovery_code_usage(): void
    {
        $loginId = $this->getTwoFactorAuthenticationLoginId();

        $response = $this->postJson(route('api.v1.two-factor-challenge'), [
            'login_id' => $loginId,
            'code' => '',
            'recovery_code' => '123456',
            'device_name' => 'device_name',
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_successful_recovery_code_usage(): void
    {
        $loginId = $this->getTwoFactorAuthenticationLoginId();
        $jwt = app(Jwt::class);
        $id = $jwt->decode($loginId)['uid'];

        /** @var User */
        $user = User::find($id);

        $code = collect($user->recoveryCodes())->first();

        $response = $this->postJson(route('api.v1.two-factor-challenge'), [
            'login_id' => $loginId,
            'code' => '',
            'recovery_code' => $code,
            'device_name' => 'device_name',
        ]);

        $response->assertStatus(JsonResponse::HTTP_OK);
        $response->assertExactJsonStructure(['token']);
    }

    public function test_successful_code_usage(): void
    {
        $loginId = $this->getTwoFactorAuthenticationLoginId();
        $jwt = app(Jwt::class);
        $id = $jwt->decode($loginId)['uid'];

        /** @var User */
        $user = User::find($id);

        /** @var string */
        $secret = $user->two_factor_secret;
        /** @var string */
        $decryptedSecret = decrypt($secret);

        $google2fa = new Google2FA;
        $otp = $google2fa->getCurrentOtp($decryptedSecret);

        $response = $this->postJson(route('api.v1.two-factor-challenge'), [
            'login_id' => $loginId,
            'code' => $otp,
            'recovery_code' => '',
            'device_name' => 'device_name',
        ]);

        $response->assertStatus(JsonResponse::HTTP_OK);
        $response->assertExactJsonStructure(['token']);
    }

    public function test_invalid_login_id(): void
    {
        $response = $this->postJson(route('api.v1.two-factor-challenge'), [
            'login_id' => 'invalid_login_id',
            'code' => '123456',
            'recovery_code' => '',
            'device_name' => 'device_name',
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNAUTHORIZED);
    }

    public function test_invalid_code_and_recovery_code(): void
    {
        $loginId = $this->getTwoFactorAuthenticationLoginId();

        $response = $this->postJson(route('api.v1.two-factor-challenge'), [
            'login_id' => $loginId,
            'code' => 'invalid_code',
            'recovery_code' => 'invalid_recovery_code',
            'device_name' => 'device_name',
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_missing_code_and_recovery_code(): void
    {
        $loginId = $this->getTwoFactorAuthenticationLoginId();

        $response = $this->postJson(route('api.v1.two-factor-challenge'), [
            'login_id' => $loginId,
            'code' => '',
            'recovery_code' => '',
            'device_name' => 'device_name',
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_device_name_is_required(): void
    {
        $loginId = $this->getTwoFactorAuthenticationLoginId();

        $response = $this->postJson(route('api.v1.two-factor-challenge'), [
            'login_id' => $loginId,
            'code' => '123456',
            'recovery_code' => '',
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['device_name']);
    }

    private function getTwoFactorAuthenticationLoginId(): string
    {
        $provider = app(TwoFactorAuthenticationProvider::class);
        $user = User::factory()->create([
            'two_factor_secret' => encrypt($provider->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode(Collection::times(8, function () {
                return RecoveryCode::generate();
            }))),
        ] + (Fortify::confirmsTwoFactorAuthentication() ? ['two_factor_confirmed_at' => now()] : []));
        $response = $this->postJson(route('api.v1.login'), [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'device_name',
        ]);

        /** @var string */
        $loginId = $response->json('login_id');

        return $loginId;
    }

    public function test_two_factor_challenge_endpoint_is_throttled(): void
    {
        /** @var int */
        $limit = config('api.limit_per_minute', 5);

        $loginId = $this->getTwoFactorAuthenticationLoginId();
        $limit--;

        foreach (range(0, $limit) as $i) {
            $response = $this->postJson(route('api.v1.two-factor-challenge'), [
                'login_id' => $loginId,
                'code' => '123456',
                'recovery_code' => '',
                'device_name' => 'device_name',
            ]);

            if ($i < $limit) {
                $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            } else {
                $response->assertTooManyRequests();
            }
        }
    }
}
