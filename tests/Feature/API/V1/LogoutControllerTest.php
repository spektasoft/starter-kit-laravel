<?php

namespace Tests\Feature\Api\V1;

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
}
