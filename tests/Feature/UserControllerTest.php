<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_route_returns_json_response(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->getJson(route('user'));
        $response->assertJson($user->toArray());
        $response->assertStatus(200);
    }

    public function test_user_route_redirects_to_profile_page(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->get(route('user'));
        $response->assertRedirect(route('profile.show'));
    }

    public function test_user_route_is_protected_by_auth_sanctum_middleware(): void
    {
        $response = $this->get(route('user'));
        $response->assertRedirect(route('login'));
    }
}
