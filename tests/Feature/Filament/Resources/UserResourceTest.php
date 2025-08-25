<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Jetstream\Jetstream;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.super_users' => ['super@example.com', 'super2@example.com']]);
        $user = User::factory()->create();
        $this->actingAs($user);
        Permission::firstOrCreate(['name' => 'view_any_user']);
        $user->givePermissionTo('view_any_user');
        Permission::firstOrCreate(['name' => 'view_user']);
        $user->givePermissionTo('view_user');
        Permission::firstOrCreate(['name' => 'create_user']);
        $user->givePermissionTo('create_user');
        Permission::firstOrCreate(['name' => 'update_user']);
        $user->givePermissionTo('update_user');
        Permission::firstOrCreate(['name' => 'delete_user']);
        $user->givePermissionTo('delete_user');
        Permission::firstOrCreate(['name' => 'delete_any_user']);
        $user->givePermissionTo('delete_any_user');
    }

    public function test_user_list_page_can_be_rendered(): void
    {
        $this->get(UserResource::getUrl('index'))->assertSuccessful();
    }

    public function test_user_create_page_can_be_rendered(): void
    {
        $this->get(UserResource::getUrl('create'))->assertSuccessful();
    }

    public function test_user_edit_page_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $this->get(UserResource::getUrl('edit', ['record' => $user]))->assertSuccessful();
    }

    public function test_can_create_a_new_user(): void
    {
        $newUser = User::factory()->make();
        $password = 'password123';

        $livewire = Livewire::test(CreateUser::class);
        $livewire->fillForm([
            'name' => $newUser->name,
            'email' => $newUser->email,
            'password' => $password,
        ]);
        $livewire->call('create');
        $livewire->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'name' => $newUser->name,
            'email' => $newUser->email,
        ]);

        $createdUser = User::where('email', $newUser->email)->first();
        $this->assertTrue(Hash::check($password, $createdUser->password ?? ''));
    }

    public function test_can_edit_an_existing_user(): void
    {
        $user = User::factory()->create();
        $newName = 'Updated Name';
        $newEmail = 'updated@example.com';

        $livewire = Livewire::test(EditUser::class, ['record' => $user->id]);
        $livewire->fillForm([
            'name' => $newName,
            'email' => $newEmail,
        ]);
        $livewire->call('save');
        $livewire->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $newName,
            'email' => $newEmail,
        ]);
    }

    public function test_validates_required_fields_on_create(): void
    {
        $livewire = Livewire::test(CreateUser::class);
        $livewire->fillForm([
            'name' => '',
            'email' => '',
            'password' => '',
        ]);
        $livewire->call('create');
        $livewire->assertHasFormErrors([
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);
    }

    public function test_validates_unique_email_on_create(): void
    {
        $existingUser = User::factory()->create();
        $newUser = User::factory()->make();

        $livewire = Livewire::test(CreateUser::class);
        $livewire->fillForm([
            'name' => $newUser->name,
            'email' => $existingUser->email, // Use existing email
            'password' => 'password123',
        ]);
        $livewire->call('create');
        $livewire->assertHasFormErrors([
            'email' => 'unique',
        ]);
    }

    public function test_validates_unique_email_on_edit_ignoring_self(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $livewire = Livewire::test(EditUser::class, ['record' => $user1->id]);
        $livewire->fillForm([
            'email' => $user2->email, // Try to use user2's email
        ]);
        $livewire->call('save');
        $livewire->assertHasFormErrors([
            'email' => 'unique',
        ]);

        // Should pass if email is the same as the current user's
        $livewire = Livewire::test(EditUser::class, ['record' => $user1->id]);
        $livewire->fillForm([
            'email' => $user1->email,
        ]);
        $livewire->call('save');
        $livewire->assertHasNoFormErrors();
    }

    public function test_user_table_has_expected_columns(): void
    {
        $livewire = Livewire::test(UserResource\Pages\ListUsers::class);
        $livewire->assertCanRenderTableColumn('name');
        $livewire->assertCanRenderTableColumn('email');
        $livewire->assertCanRenderTableColumn('roles');
        $livewire->assertCanRenderTableColumn('email_verified_at');
        $livewire->assertCanRenderTableColumn('created_at');
        $livewire->assertCanRenderTableColumn('updated_at');

        if (Jetstream::managesProfilePhotos()) {
            Livewire::test(UserResource\Pages\ListUsers::class)
                ->assertCanRenderTableColumn('profile_photo_media_id');
        }
    }

    public function test_created_at_and_updated_at_columns_are_hidden_by_default(): void
    {
        User::factory()->create();

        /** @var ListUsers */
        $livewire = Livewire::test(ListUsers::class)->instance();
        $table = $livewire->getTable();

        $this->assertTrue($table->getColumn('created_at')?->isToggledHidden());
        $this->assertTrue($table->getColumn('updated_at')?->isToggledHidden());
    }

    public function test_super_users_are_not_listed_for_non_super_admin(): void
    {
        // setUp() user is a non-super-admin
        config(['auth.super_users' => ['super@example.com']]);
        $superUser = User::factory()->create(['email' => 'super@example.com']);

        Livewire::test(UserResource\Pages\ListUsers::class)
            ->assertCanNotSeeTableRecords([$superUser]);
    }

    public function test_super_users_are_listed_for_super_admin(): void
    {
        config(['auth.super_users' => ['super1@example.com', 'super2@example.com']]);
        $superAdmin = User::factory()->create(['email' => 'super1@example.com']);
        $otherSuperUser = User::factory()->create(['email' => 'super2@example.com']);
        $this->actingAs($superAdmin);

        $livewire = Livewire::test(UserResource\Pages\ListUsers::class);
        $livewire->assertCanSeeTableRecords([$otherSuperUser]);
        $livewire->assertTableColumnStateSet('roles', '["Super User"]', $otherSuperUser);
    }

    public function test_roles_column_displays_assigned_roles_correctly(): void
    {
        $user = User::factory()->create();
        $role1 = Role::create(['name' => 'admin']);
        $role2 = Role::create(['name' => 'editor']);
        $user->assignRole($role1, $role2);

        $livewire = Livewire::test(UserResource\Pages\ListUsers::class);
        $livewire->assertCanSeeTableRecords([$user]);
        $livewire->assertTableColumnStateSet('roles', '["Admin","Editor"]', $user);
    }

    public function test_can_delete_a_user(): void
    {
        $userToDelete = User::factory()->create();

        $livewire = Livewire::test(UserResource\Pages\ListUsers::class);
        $livewire->callTableAction(
            'delete',
            $userToDelete
        );
        $livewire->assertHasNoTableActionErrors();

        $this->assertModelMissing($userToDelete);
    }

    public function test_can_bulk_delete_users(): void
    {
        $usersToDelete = User::factory()->count(3)->create();

        $livewire = Livewire::test(UserResource\Pages\ListUsers::class);
        $livewire->callTableBulkAction(
            'delete',
            $usersToDelete
        );
        $livewire->assertHasNoTableBulkActionErrors();

        foreach ($usersToDelete as $user) {
            $this->assertModelMissing($user);
        }
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

    public function test_cannot_delete_a_user_that_is_referenced(): void
    {
        // Assuming a User has a relationship with another model, e.g., Page
        $userToDelete = User::factory()->has(Page::factory())->create();

        Livewire::test(UserResource\Pages\ListUsers::class)
            ->assertTableActionHidden('delete', $userToDelete);

        $this->assertModelExists($userToDelete);
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

        $listUsers = Livewire::test(UserResource\Pages\ListUsers::class);
        $listUsers->assertTableActionHidden('delete', $userToDelete);
    }

    public function test_cannot_bulk_delete_users_without_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user); // User without 'delete_user' permission
        $user->givePermissionTo('view_any_user');
        $user->givePermissionTo('delete_any_user');
        $usersToDelete = User::factory(2)->create();

        $listUsers = Livewire::test(UserResource\Pages\ListUsers::class);
        $listUsers->callTableBulkAction('delete', $usersToDelete);

        $this->assertEquals(User::count(), 4); // 3 users + the initial user
    }
}
