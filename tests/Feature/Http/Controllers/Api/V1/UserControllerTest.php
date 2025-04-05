<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Policies\UserPolicyTest;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        UserPolicyTest::setUpPermissions();
    }

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
        $user->givePermissionTo('view_any_user');
        Sanctum::actingAs($user, ['read']);

        $response = $this->getJson(route('api.v1.users.index'));
        $response->assertStatus(JsonResponse::HTTP_OK);
        $this->assertCount($count, (array) $response->json('data'));
    }

    public function test_show_user(): void
    {
        /** @var User */
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $user->givePermissionTo('view_user');
        Sanctum::actingAs($user, ['read']);

        $response = $this->getJson(route('api.v1.users.show', $user->id));

        $response->assertStatus(JsonResponse::HTTP_OK);

        $response->assertJsonStructure([
            'id',
            'name',
            'email',
        ]);

        $response->assertJson([
            'id' => $user->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_create_user_success(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $user->givePermissionTo('create_user');
        $token = $user->createToken('test-token', ['create'])->plainTextToken;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson(route('api.v1.users.store'), [
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
        $user->givePermissionTo('create_user');
        $token = $user->createToken('test-token', ['create'])->plainTextToken;
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
        $user->givePermissionTo('create_user');
        $token = $user->createToken('test-token', ['create'])->plainTextToken;
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

    public function test_update_user(): void
    {
        $existingUser = User::create([
            'name' => 'Existing User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        /** @var User */
        $user = User::factory()->create();
        $user->givePermissionTo('update_user');
        Sanctum::actingAs($user, ['update']);

        $response = $this->putJson(route('api.v1.users.update', ['user' => $existingUser->id]), [
            'name' => 'Edit Existing User',
            'email' => 'testedit@example.com',
            'password' => 'newpassword',
        ]);

        $response->assertStatus(JsonResponse::HTTP_ACCEPTED);

        /** @var User */
        $freshUser = $existingUser->fresh();
        $this->assertEquals($freshUser->name, 'Edit Existing User');
        $this->assertEquals($freshUser->email, 'testedit@example.com');
        $this->assertTrue(Hash::check('newpassword', $freshUser->password));
    }

    public function test_update_user_name_only(): void
    {
        $existingUser = User::create([
            'name' => 'Existing User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        /** @var User */
        $user = User::factory()->create();
        $user->givePermissionTo('update_user');
        Sanctum::actingAs($user, ['update']);

        $response = $this->putJson(route('api.v1.users.update', ['user' => $existingUser->id]), [
            'name' => 'Edit Existing User',
        ]);

        $response->assertStatus(JsonResponse::HTTP_ACCEPTED);

        /** @var User */
        $freshUser = $existingUser->fresh();
        $this->assertEquals($freshUser->name, 'Edit Existing User');
        $this->assertEquals($freshUser->email, 'test@example.com');
        $this->assertTrue(Hash::check('password', $freshUser->password));
    }

    public function test_update_user_email_only(): void
    {
        $existingUser = User::create([
            'name' => 'Existing User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        /** @var User */
        $user = User::factory()->create();
        $user->givePermissionTo('update_user');
        Sanctum::actingAs($user, ['update']);

        $response = $this->putJson(route('api.v1.users.update', ['user' => $existingUser->id]), [
            'email' => 'testedit@example.com',
        ]);

        $response->assertStatus(JsonResponse::HTTP_ACCEPTED);

        /** @var User */
        $freshUser = $existingUser->fresh();
        $this->assertEquals($freshUser->name, 'Existing User');
        $this->assertEquals($freshUser->email, 'testedit@example.com');
        $this->assertTrue(Hash::check('password', $freshUser->password));
    }

    public function test_update_user_password_only(): void
    {
        $existingUser = User::create([
            'name' => 'Existing User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        /** @var User */
        $user = User::factory()->create();
        $user->givePermissionTo('update_user');
        Sanctum::actingAs($user, ['update']);

        $response = $this->putJson(route('api.v1.users.update', ['user' => $existingUser->id]), [
            'password' => 'newpassword',
        ]);

        $response->assertStatus(JsonResponse::HTTP_ACCEPTED);

        /** @var User */
        $freshUser = $existingUser->fresh();
        $this->assertEquals($freshUser->name, 'Existing User');
        $this->assertEquals($freshUser->email, 'test@example.com');
        $this->assertTrue(Hash::check('newpassword', $freshUser->password));
    }

    public function test_delete_a_user(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $user->givePermissionTo('delete_user');
        Sanctum::actingAs($user, ['delete']);

        $targetedUser = User::factory()->create();
        $this->assertDatabaseHas('users', ['id' => $targetedUser->id]);

        $response = $this->deleteJson(route('api.v1.users.destroy', $targetedUser->id));

        $response->assertStatus(JsonResponse::HTTP_OK);
        $response->assertJson(['message' => 'User deleted successfully!']);
        $this->assertDatabaseMissing('users', ['id' => $targetedUser->id]);
    }

    public function test_current_user_permission_granted(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $user->givePermissionTo('view_any_user');
        $this->actingAs($user);
        $response = $this->postJson(route('api.v1.user.can'), [
            'action' => 'viewAny',
            'resource' => 'users',
        ]);
        $response->assertSuccessful();
    }

    public function test_current_user_permission_denied(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $user->givePermissionTo('view_any_user');
        $this->actingAs($user);
        $response = $this->postJson(route('api.v1.user.can'), [
            'action' => 'deleteAny',
            'resource' => 'users',
        ]);
        $response->assertForbidden();
    }

    public function test_user_endpoint_is_throttled(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        /** @var int */
        $limit = config('api.limit_per_minute', 5);

        foreach (range(0, $limit) as $i) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->getJson(route('api.v1.user'));

            if ($i < $limit) {
                $response->assertSuccessful();
            } else {
                $response->assertTooManyRequests();
            }
        }
    }
}
