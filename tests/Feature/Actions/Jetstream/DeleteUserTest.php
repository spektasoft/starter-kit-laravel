<?php

namespace Tests\Feature\Actions\Jetstream;

use App\Actions\Jetstream\DeleteUser;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeleteUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_active_resources_cannot_be_deleted()
    {
        // Create a user with an active Page resource
        $user = User::factory()->create();
        Page::factory()->create(['creator_id' => $user->id]);

        // Verify the user is referenced
        $this->assertTrue($user->isReferenced());

        // Attempt to delete the user
        $deleteUserAction = app(DeleteUser::class);

        try {
            $deleteUserAction->delete($user);
            $this->fail('ValidationException was not thrown');
        } catch (ValidationException $e) {
            // Verify the correct error message
            $this->assertEquals(
                __('user.account_cannot_be_deleted'),
                $e->errors()['delete_account'][0]
            );
        }

        // Verify the user still exists in the database
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $user->name,
        ]);

        // Verify the associated Page still exists
        $this->assertDatabaseHas('pages', [
            'creator_id' => $user->id,
        ]);
    }

    public function test_user_without_active_resources_can_be_deleted()
    {
        // Create a user without any active resources
        $user = User::factory()->create();

        // Verify the user is not referenced
        $this->assertFalse($user->isReferenced());

        // Attempt to delete the user
        $deleteUserAction = app(DeleteUser::class);
        $deleteUserAction->delete($user);

        // Verify the user no longer exists in the database
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }
}
