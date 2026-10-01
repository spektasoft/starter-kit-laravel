<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

class UserResourceAuthorizationTest extends UserResourceTestCase
{
    public function test_super_users_are_not_listed_for_non_super_admin(): void
    {
        // setUp() user is a non-super-admin
        config(['auth.super_users' => ['super@example.com']]);
        $superUser = User::factory()->create(['email' => 'super@example.com']);

        Livewire::test(ListUsers::class)
            ->assertCanNotSeeTableRecords([$superUser]);
    }

    public function test_super_users_are_listed_for_super_admin(): void
    {
        config(['auth.super_users' => ['super1@example.com', 'super2@example.com']]);
        $superAdmin = User::factory()->create(['email' => 'super1@example.com']);
        $otherSuperUser = User::factory()->create(['email' => 'super2@example.com']);
        $this->actingAs($superAdmin);

        $livewire = Livewire::test(ListUsers::class);
        $livewire->assertCanSeeTableRecords([$otherSuperUser]);
        $livewire->assertTableColumnStateSet('roles', '["Super User"]', $otherSuperUser);
    }

    public function test_eloquent_query_excludes_authenticated_user(): void
    {
        $authenticatedUser = User::factory()->create();
        $this->actingAs($authenticatedUser);

        $otherUser = User::factory()->create();

        $query = UserResource::getEloquentQuery();
        $users = $query->get();

        $this->assertFalse($users->contains($authenticatedUser));
        $this->assertTrue($users->contains($otherUser));
    }

    public function test_eloquent_query_excludes_super_users_if_not_super_user(): void
    {
        // Authenticate as a regular user
        $regularUser = User::factory()->create();
        $this->actingAs($regularUser);

        // Create a super user
        /** @var array<?string> */
        $config = config('auth.super_users');
        $superUserEmail = $config[0] ?? 'superuser@example.com';
        $superUser = User::factory()->create(['email' => $superUserEmail]);

        // Create another regular user
        $anotherRegularUser = User::factory()->create();

        $query = UserResource::getEloquentQuery();
        $users = $query->get();

        $this->assertFalse($users->contains($superUser));
        $this->assertTrue($users->contains($anotherRegularUser));
        $this->assertFalse($users->contains($regularUser)); // Authenticated user is also excluded
    }

    public function test_eloquent_query_includes_super_users_if_authenticated_as_super_user(): void
    {
        // Authenticate as a super user
        /** @var array<?string> */
        $config = config('auth.super_users');
        $superUserEmail = $config[0] ?? 'superuser@example.com';
        $authenticatedSuperUser = User::factory()->create(['email' => $superUserEmail]);
        $this->actingAs($authenticatedSuperUser);

        // Create another super user
        /** @var string|null */
        $anotherSuperUserEmail = $config[1] ?? 'another_superuser@example.com';
        $anotherSuperUser = User::factory()->create(['email' => $anotherSuperUserEmail]);

        // Create a regular user
        $regularUser = User::factory()->create();

        $query = UserResource::getEloquentQuery();
        $users = $query->get();

        // The authenticated super user should still be excluded by the `where('id', '!=', User::auth()?->id)` clause
        $this->assertFalse($users->contains($authenticatedSuperUser));
        $this->assertTrue($users->contains($anotherSuperUser));
        $this->assertTrue($users->contains($regularUser));
    }

    public function test_cannot_render_create_page_without_permission(): void
    {
        $user = User::factory()->create(); // User without 'create_user' permission
        $this->actingAs($user);

        $this->get(UserResource::getUrl('create'))->assertForbidden();
    }

    public function test_cannot_render_edit_page_without_permission(): void
    {
        $user = User::factory()->create(); // User without 'update_user' permission
        $this->actingAs($user);
        $userToEdit = User::factory()->create();

        $this->get(UserResource::getUrl('edit', ['record' => $userToEdit]))->assertForbidden();
    }

    public function test_cannot_delete_user_without_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user); // User without 'delete_user' permission
        $user->givePermissionTo('view_any_user');
        $userToDelete = User::factory()->create();

        $listUsers = Livewire::test(ListUsers::class);
        $listUsers->assertTableActionHidden('delete', $userToDelete);
    }

    public function test_cannot_bulk_delete_users_without_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user); // User without 'delete_user' permission
        $user->givePermissionTo('view_any_user');
        $user->givePermissionTo('delete_any_user');
        $usersToDelete = User::factory(2)->create();

        $initialCount = User::count();

        $listUsers = Livewire::test(ListUsers::class);
        $listUsers->selectTableRecords($usersToDelete->pluck('id')->toArray())
            ->callAction(TestAction::make('delete')->table()->bulk());

        $this->assertEquals($initialCount, User::count());
    }
}
