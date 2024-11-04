<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_users(): void
    {
        $count = 5;

        for ($i = 0; $i < $count; $i++) {
            User::factory()->create();
        }

        /** @var User */
        $user = User::first();
        $this->actingAs($user);

        $response = $this->getJson(route('api.v1.users.index'));

        $response->assertStatus(200);
        $this->assertCount($count, (array) $response->json('data'));
    }
}
