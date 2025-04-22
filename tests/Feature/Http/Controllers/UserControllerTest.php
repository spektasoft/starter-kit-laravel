<?php

namespace Tests\Feature\Http\Controllers;

use App\Data\UserData;
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
        $response->assertJson(UserData::from($user)->toArray());
        $response->assertStatus(200);
    }

    public function test_user_route_is_protected_by_auth_sanctum_middleware(): void
    {
        $response = $this->get(route('user'));
        $response->assertRedirect(route('login'));
    }
}
