<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
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

    public function test_create_user_success(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson(route('api.v1.users.create'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(JsonResponse::HTTP_CREATED);
        $response->assertJson(['message' => 'User created successfully!']);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_create_user_validation_error(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $client = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ]);

        // Test missing name
        $response = $client->postJson('/users', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['errors' => ['name']]);

        // Test invalid email
        $response = $client->postJson('/users', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password',
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['errors' => ['email']]);

        // Test missing password
        $response = $client->postJson('/users', [
            'name' => 'Test User',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['errors' => ['password']]);
    }

    public function test_create_user_email_already_exists(): void
    {
        User::create([
            'name' => 'Existing User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        /** @var User */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $client = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ]);

        $response = $client->postJson('/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonStructure(['errors' => ['email']]);
    }
}
