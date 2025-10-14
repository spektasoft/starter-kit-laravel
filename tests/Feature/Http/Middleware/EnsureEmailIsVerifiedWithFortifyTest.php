<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\EnsureEmailIsVerifiedWithFortify;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Mockery;
use Tests\TestCase;

class EnsureEmailIsVerifiedWithFortifyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure FortifyFeatures is not mocked globally by other tests
        Mockery::close();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_guest_users_are_not_affected_by_the_middleware(): void
    {
        Route::get('/test-route', function () {
            return response('OK', 200);
        })->middleware(EnsureEmailIsVerifiedWithFortify::class);

        $response = $this->get('/test-route');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_unverified_user_is_allowed_to_proceed_when_fortify_feature_is_disabled(): void
    {
        // Temporarily disable Fortify's email verification feature for this test
        config(['fortify.features' => []]);

        // Create an unverified user
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($user);

        Route::get('/test-route', function () {
            return response('OK', 200);
        })->middleware(EnsureEmailIsVerifiedWithFortify::class);

        $response = $this->get('/test-route');
        $response->assertStatus(200);
        $response->assertSee('OK');
    }

    public function test_verified_user_is_allowed_to_proceed(): void
    {
        // Ensure Fortify's email verification feature is enabled for this test
        config(['fortify.features' => [Features::emailVerification()]]);

        // Create a verified user
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user);

        Route::get('/test-route', function () {
            return response('OK', 200);
        })->middleware(
            EnsureEmailIsVerifiedWithFortify::class);

        $response = $this->get('/test-route');
        $response->assertStatus(200);
        $response->assertSee('OK');
    }

    public function test_unverified_user_is_redirected_to_email_verification_prompt_when_feature_is_enabled(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification support is not enabled.');
        }

        // Create an unverified user
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($user);

        Route::get('/test-route', function () {
            return response('Should not reach here', 200);
        })->middleware(EnsureEmailIsVerifiedWithFortify::class);

        $response = $this->get('/test-route');
        $response->assertStatus(302);
        /** @var string */
        $location = $response->headers->get('Location');
        $this->assertStringContainsString(route('verification.notice', absolute: false), $location);
    }
}
