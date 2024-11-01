<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_their_own_data(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->getJson(route('api.v1.user'));
        $response->assertSuccessful();
        $response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function test_authenticated_user_can_access_their_own_data_using_sanctum_token(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson(route('api.v1.user'));
        $response->assertSuccessful();
        $response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_their_own_data(): void
    {
        $response = $this->getJson(route('api.v1.user'));
        $response->assertUnauthorized();
    }
}
