<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Media;
use App\Models\Page;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Jetstream;
use Livewire\Livewire;

class UserResourceTest extends UserResourceTestCase
{
    public function test_profile_photo_field_and_column_follow_jetstream_features(): void
    {
        $user = User::factory()->create();

        config(['jetstream.features' => [Features::profilePhotos()]]);
        Livewire::test(CreateUser::class)->assertFormFieldExists('profile_photo_media_id');
        Livewire::test(EditUser::class, ['record' => $user->id])
            ->assertFormFieldExists('profile_photo_media_id');
        Livewire::test(ListUsers::class)->assertTableColumnExists('profile_photo_media_id');

        config(['jetstream.features' => []]);
        Livewire::test(CreateUser::class)->assertFormFieldDoesNotExist('profile_photo_media_id');
        Livewire::test(EditUser::class, ['record' => $user->id])
            ->assertFormFieldDoesNotExist('profile_photo_media_id');
        Livewire::test(ListUsers::class)->assertTableColumnDoesNotExist('profile_photo_media_id');
    }

    public function test_editing_with_blank_password_preserves_the_existing_password(): void
    {
        $user = User::factory()->create();
        $hash = $user->password;

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['name' => 'Updated name', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Updated name', $user->refresh()->name);
        $this->assertSame($hash, $user->password);
    }

    public function test_editing_with_a_new_password_persists_a_hash(): void
    {
        $user = User::factory()->create();
        $hash = $user->password;

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['password' => 'new-password-123'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNotSame($hash, $user->refresh()->password);
        $this->assertTrue(Hash::check('new-password-123', $user->password));
    }

    public function test_profile_photo_media_attribute_is_saved_when_enabled(): void
    {
        config(['jetstream.features' => [Features::profilePhotos()]]);
        $user = User::factory()->create();
        $authenticatedUser = User::auth();
        $this->assertInstanceOf(User::class, $authenticatedUser);
        $media = Media::factory()->create(['creator_id' => $authenticatedUser->id]);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->set('data.profile_photo_media_id', [$media->toArray()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($media->id, $user->refresh()->profile_photo_media_id);
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
        $livewire = Livewire::test(ListUsers::class);
        $livewire->assertCanRenderTableColumn('name');
        $livewire->assertCanRenderTableColumn('email');
        $livewire->assertCanRenderTableColumn('roles');
        $livewire->assertCanRenderTableColumn('email_verified_at');

        if (Jetstream::managesProfilePhotos()) {
            Livewire::test(ListUsers::class)
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

    public function test_roles_column_displays_assigned_roles_correctly(): void
    {
        $user = User::factory()->create();
        $role1 = Role::create(['name' => 'admin']);
        $role2 = Role::create(['name' => 'editor']);
        $user->assignRole($role1, $role2);

        $livewire = Livewire::test(ListUsers::class);
        $livewire->assertCanSeeTableRecords([$user]);
        $livewire->assertTableColumnStateSet('roles', '["Admin","Editor"]', $user);
    }

    public function test_can_delete_a_user(): void
    {
        $userToDelete = User::factory()->create();

        $livewire = Livewire::test(ListUsers::class);
        $livewire->callAction(TestAction::make('delete')->table($userToDelete));
        $livewire->assertHasNoFormErrors();

        $this->assertModelMissing($userToDelete);
    }

    public function test_can_bulk_delete_users(): void
    {
        $usersToDelete = User::factory()->count(3)->create();

        $livewire = Livewire::test(ListUsers::class);
        $livewire->selectTableRecords($usersToDelete->pluck('id')->toArray())
            ->callAction(TestAction::make('delete')->table()->bulk());
        $livewire->assertHasNoFormErrors();

        foreach ($usersToDelete as $user) {
            $this->assertModelMissing($user);
        }
    }

    public function test_cannot_delete_a_user_that_is_referenced(): void
    {
        // Assuming a User has a relationship with another model, e.g., Page
        $userToDelete = User::factory()->has(Page::factory())->create();

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('delete', $userToDelete);

        $this->assertModelExists($userToDelete);
    }

    public function test_user_global_search_is_configured_correctly(): void
    {
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        /** @var Collection<string|int, mixed> */
        $results = Filament::getGlobalSearchProvider()
            ?->getResults('John Doe')
            ?->getCategories()
            ->get(UserResource::getPluralModelLabel(), collect());

        $this->assertCount(1, $results);
        /** @var GlobalSearchResult */
        $first = $results->first();
        $this->assertEquals('John Doe', $first->title);
        $this->assertEquals('john@example.com', $first->details['Email']);
    }
}
