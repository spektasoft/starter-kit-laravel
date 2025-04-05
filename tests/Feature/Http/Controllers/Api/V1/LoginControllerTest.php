<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credentials_return_token(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson(route('api.v1.login'), [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
        ]);
    }

    public function test_invalid_credentials_return_error(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson(route('api.v1.login'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'errors',
        ]);
    }

    public function test_missing_fields_return_error(): void
    {
        $response = $this->postJson(route('api.v1.login'), [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'errors',
        ]);
    }

    public function test_invalid_email_format_return_error(): void
    {
        $response = $this->postJson(route('api.v1.login'), [
            'email' => 'invalid-email',
            'password' => 'password',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'errors',
        ]);
    }

    public function test_user_not_found_returns_error(): void
    {
        $response = $this->postJson(route('api.v1.login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'errors',
        ]);
    }

    public function test_valid_credentials_with_2fa_enabled_returns_2fa_challenge(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'two_factor_secret' => encrypt('some_secret'),
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->postJson(route('api.v1.login'), [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'login_id',
            'two_factor',
        ]);
        $this->assertTrue($response['two_factor']);
    }

    public function test_valid_credentials_with_2fa_enabled_but_not_confirmed_returns_token_if_confirmation_not_required(): void
    {
        config(['fortify.features' => [
            Features::twoFactorAuthentication([
                'confirm' => false,
            ]),
        ]]);

        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'two_factor_secret' => encrypt('some_secret'),
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->postJson(route('api.v1.login'), [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'login_id',
            'two_factor',
        ]);
        $this->assertTrue($response['two_factor']);
    }

    public function test_valid_credentials_with_2fa_enabled_but_not_confirmed_returns_2fa_challenge_if_confirmation_required(): void
    {
        config(['fortify.features' => [
            Features::twoFactorAuthentication([
                'confirm' => true,
            ]),
        ]]);

        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'two_factor_secret' => encrypt('some_secret'),
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->postJson(route('api.v1.login'), [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
        ]);
    }

    public function test_username_casing_insensitivity(): void
    {
        config(['fortify.lowercase_usernames' => true]);

        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson(route('api.v1.login'), [
            'email' => 'TEST@example.com',
            'password' => 'password',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
        ]);
    }

    public function test_device_name_is_required(): void
    {
        $response = $this->postJson(route('api.v1.login'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'errors',
        ]);
        /** @var array<string, array<mixed>> $response */
        $this->assertArrayHasKey('device_name', $response['errors']);
    }

    public function test_device_name_is_sanitized(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson(route('api.v1.login'), [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => '<script>alert("XSS")</script>',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
        ]);

        $this->assertStringNotContainsString('<script>', User::first()?->tokens()->first()->name ?? '');
    }

    public function test_device_name_is_trimmed(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $deviceNameWithSpaces = '   Test Device With Spaces   ';

        $response = $this->postJson(route('api.v1.login'), [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => $deviceNameWithSpaces,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
        ]);

        $this->assertEquals(trim($deviceNameWithSpaces), User::first()?->tokens()?->first()?->name);
    }

    public function test_device_name_is_not_empty(): void
    {
        $response = $this->postJson(route('api.v1.login'), [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'errors',
        ]);
        /** @var array<string, array<mixed>> $response */
        $this->assertArrayHasKey('device_name', $response['errors']);
    }

    public function test_login_endpoint_is_throttled(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        /** @var int */
        $limit = config('api.limit_per_minute', 5);

        foreach (range(0, $limit) as $i) {
            $response = $this->postJson(route('api.v1.login'), [
                'email' => 'test@example.com',
                'password' => 'password',
                'device_name' => 'Test Device',
            ]);

            if ($i < $limit) {
                $response->assertSuccessful();
            } else {
                $response->assertTooManyRequests();
            }
        }
    }
}
