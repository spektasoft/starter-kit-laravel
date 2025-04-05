<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_successfully(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $this->assertEquals($user->tokens()->count(), 1);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson(route('api.v1.logout'));
        $response->assertSuccessful();

        $this->assertEquals($user->tokens()->count(), 0);
    }

    public function test_logout_no_token(): void
    {
        /** @var User */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('api.v1.logout'));

        $response->assertSuccessful();
        $response->assertJson([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function test_logout_unauthorized(): void
    {
        $response = $this->postJson(route('api.v1.logout'));

        $response->assertStatus(401);
    }

    public function test_logout_endpoint_is_throttled(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        /** @var int */
        $limit = config('api.limit_per_minute', 5);

        foreach (range(0, $limit) as $i) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->postJson(route('api.v1.logout'));

            if ($i < $limit) {
                $response->assertSuccessful();
            } else {
                $response->assertTooManyRequests();
            }
        }
    }
}
