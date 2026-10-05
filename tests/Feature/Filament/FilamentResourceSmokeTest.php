<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Exports\ExportResource;
use App\Filament\Resources\Exports\Pages\ListExports;
use App\Filament\Resources\Imports\ImportResource;
use App\Filament\Resources\Imports\Pages\ListImports;
use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Media\Pages\CreateMedia;
use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Permissions\Pages\ListPermissions;
use App\Filament\Resources\Permissions\PermissionResource;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Export;
use App\Models\Import;
use App\Models\Media;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentResourceSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        config(['auth.super_users' => [$this->admin->email]]);
    }

    public function test_user_resource_list_page_renders_and_displays_record(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin);

        $component = Livewire::test(ListUsers::class);
        $component->assertSuccessful();
        $component->assertCanSeeTableRecords([$user]);
    }

    public function test_page_resource_list_page_renders_and_displays_record(): void
    {
        $page = Page::factory()->create(['creator_id' => $this->admin->id]);

        $this->actingAs($this->admin);

        $component = Livewire::test(ListPages::class);
        $component->assertSuccessful();
        $component->assertCanSeeTableRecords([$page]);
    }

    public function test_media_resource_list_page_renders_and_displays_record(): void
    {
        $media = Media::factory()->create(['creator_id' => $this->admin->id]);

        $this->actingAs($this->admin);

        $component = Livewire::test(ListMedia::class);
        $component->assertSuccessful();
        $component->assertCanSeeTableRecords([$media]);
    }

    public function test_role_resource_list_page_renders_and_displays_record(): void
    {
        $role = Role::factory()->create();

        $this->actingAs($this->admin);

        $component = Livewire::test(ListRoles::class);
        $component->assertSuccessful();
        $component->assertCanSeeTableRecords([$role]);
    }

    public function test_permission_resource_list_page_renders_and_displays_record(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'view_any_permission',
            'guard_name' => 'web',
        ]);

        $this->actingAs($this->admin);

        $component = Livewire::test(ListPermissions::class);
        $component->assertSuccessful();
        $component->assertCanSeeTableRecords([$permission]);
    }

    public function test_export_resource_list_page_renders_and_displays_record(): void
    {
        $export = Export::factory()->create([
            'user_id' => $this->admin->id,
            'creator_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);

        $component = Livewire::test(ListExports::class);
        $component->assertSuccessful();
        $component->assertCanSeeTableRecords([$export]);
    }

    public function test_import_resource_list_page_renders_and_displays_record(): void
    {
        $import = Import::factory()->create([
            'user_id' => $this->admin->id,
            'creator_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);

        $component = Livewire::test(ListImports::class);
        $component->assertSuccessful();
        $component->assertCanSeeTableRecords([$import]);
    }

    public function test_user_resource_create_page_renders_without_exception(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateUser::class)
            ->assertSuccessful();
    }

    public function test_user_resource_edit_page_renders_without_exception(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->assertSuccessful();
    }

    public function test_page_resource_create_page_renders_without_exception(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreatePage::class)
            ->assertSuccessful();
    }

    public function test_page_resource_edit_page_renders_without_exception(): void
    {
        $page = Page::factory()->create(['creator_id' => $this->admin->id]);

        $this->actingAs($this->admin);

        Livewire::test(EditPage::class, ['record' => $page->id])
            ->assertSuccessful();
    }

    public function test_media_resource_create_page_renders_without_exception(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateMedia::class)
            ->assertSuccessful();
    }

    public function test_media_resource_edit_page_renders_without_exception(): void
    {
        $media = Media::factory()->create(['creator_id' => $this->admin->id]);

        $this->actingAs($this->admin);

        Livewire::test(EditMedia::class, ['record' => $media->id])
            ->assertSuccessful();
    }

    public function test_role_resource_create_page_renders_without_exception(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateRole::class)
            ->assertSuccessful();
    }

    public function test_role_resource_edit_page_renders_without_exception(): void
    {
        $role = Role::factory()->create();

        $this->actingAs($this->admin);

        Livewire::test(EditRole::class, ['record' => $role->id])
            ->assertSuccessful();
    }

    public function test_unauthorized_user_receives_forbidden_on_permission_governed_routes(): void
    {
        $unauthorizedUser = User::factory()->create();
        $targetUser = User::factory()->create();
        $page = Page::factory()->create(['creator_id' => $unauthorizedUser->id]);
        $role = Role::factory()->create();

        $routes = [
            UserResource::getUrl('index'),
            UserResource::getUrl('create'),
            UserResource::getUrl('edit', ['record' => $targetUser]),
            PageResource::getUrl('index'),
            PageResource::getUrl('create'),
            PageResource::getUrl('edit', ['record' => $page]),
            RoleResource::getUrl('index'),
            RoleResource::getUrl('create'),
            RoleResource::getUrl('edit', ['record' => $role]),
            PermissionResource::getUrl('index'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($unauthorizedUser)
                ->get($url)
                ->assertForbidden();
        }
    }

    public function test_unauthorized_user_receives_forbidden_when_access_is_denied_on_scoped_routes(): void
    {
        $user = User::factory()->create();
        $media = Media::factory()->create(['creator_id' => $user->id]);

        Gate::before(fn () => false);

        $routes = [
            MediaResource::getUrl('index'),
            MediaResource::getUrl('create'),
            MediaResource::getUrl('edit', ['record' => $media]),
            ExportResource::getUrl('index'),
            ImportResource::getUrl('index'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($user)
                ->get($url)
                ->assertForbidden();
        }
    }
}
