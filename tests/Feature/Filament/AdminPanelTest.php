<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features as FortifyFeatures;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_can_access_admin_panel_when_email_verification_feature_is_not_enabled_in_fortify_config(): void
    {
        if (! FortifyFeatures::enabled(FortifyFeatures::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        if (FortifyFeatures::enabled(FortifyFeatures::emailVerification())) {
            $this->markTestSkipped('Email verification support is enabled.');
        }

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk(); // Unverified user should be able to access since middleware is not applied
    }

    public function test_unverified_user_cannot_access_admin_panel_when_email_verification_feature_is_enabled_in_fortify_config(): void
    {
        if (! FortifyFeatures::enabled(FortifyFeatures::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        if (! FortifyFeatures::enabled(FortifyFeatures::emailVerification())) {
            $this->markTestSkipped('Email verification support is not enabled.');
        }

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertRedirect('/email/verify'); // Unverified user should be redirected to email verification page
    }
}
