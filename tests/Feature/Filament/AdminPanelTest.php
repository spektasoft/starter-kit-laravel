<?php

namespace Tests\Feature\Filament;

use App\Colors\Color;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Support\Colors\Color as FilamentPalette;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
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

    public function test_verified_user_can_access_admin_panel(): void
    {
        $user = User::factory()->create(); // Verified by default

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
    }

    public function test_admin_branding_uses_the_configured_palettes_and_dashboard_icon(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertOk();

        $panel = Filament::getPanel('admin');
        $this->assertSame(Color::Vermilion, $panel->getColors()['primary']);
        $this->assertSame(Color::WebOrange, $panel->getColors()['secondary']);

        $colors = FilamentColor::getColors();
        foreach (['primary' => Color::Vermilion, 'secondary' => Color::WebOrange] as $name => $palette) {
            foreach ($palette as $shade => $hex) {
                $this->assertSame(FilamentPalette::convertToOklch($hex), $colors[$name][$shade]);
            }
        }

        $this->assertSame(Heroicon::OutlinedBuildingLibrary, Dashboard::getNavigationIcon());
    }

    public function test_filament_authentication_advisory_surfaces_are_disabled(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertFalse($panel->hasLogin());
        $this->assertFalse($panel->hasRegistration());
        $this->assertFalse($panel->hasProfile());
        $this->assertFalse($panel->hasMultiFactorAuthentication());

        $authRoutes = collect(
            Route::getRoutes()->getRoutes()
        )
            ->map(fn ($route) => $route->getName())
            ->filter(fn ($name) => str_starts_with($name ?? '', 'filament.admin.auth.'))
            ->values()
            ->all();

        $this->assertSame(['filament.admin.auth.logout'], $authRoutes);

        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_guest_user_is_redirected_to_login_page(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }
}
