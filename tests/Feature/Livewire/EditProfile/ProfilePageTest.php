<?php

namespace Tests\Feature\Livewire\EditProfile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AssertsLivewireEmbeds;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use AssertsLivewireEmbeds;
    use RefreshDatabase;

    public function test_profile_page_renders_all_edit_profile_components(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee(__('Profile'));

        $this->assertSeesLivewire(
            $response,
            'navigation-menu',
            'edit-profile.update-profile-information-form',
            'edit-profile.update-password-form',
            'edit-profile.two-factor-authentication-form',
            'edit-profile.logout-other-browser-sessions-form',
            'edit-profile.delete-user-form',
        );
    }
}
