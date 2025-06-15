<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->actingAs($user);
        Permission::firstOrCreate(['name' => 'view_any_role']);
        $user->givePermissionTo('view_any_role');
        Permission::firstOrCreate(['name' => 'view_role']);
        $user->givePermissionTo('view_role');
        Permission::firstOrCreate(['name' => 'create_role']);
        $user->givePermissionTo('create_role');
        Permission::firstOrCreate(['name' => 'update_role']);
        $user->givePermissionTo('update_role');
        Permission::firstOrCreate(['name' => 'delete_role']);
        $user->givePermissionTo('delete_role');
        Permission::firstOrCreate(['name' => 'delete_any_role']);
        $user->givePermissionTo('delete_any_role');
    }

    public function test_role_list_page_can_be_rendered(): void
    {
        $this->get(RoleResource::getUrl('index'))->assertSuccessful();
    }

    public function test_role_create_page_can_be_rendered(): void
    {
        $this->get(RoleResource::getUrl('create'))->assertSuccessful();
    }

    public function test_role_edit_page_can_be_rendered(): void
    {
        $role = Role::factory()->create();
        $this->get(RoleResource::getUrl('edit', ['record' => $role]))->assertSuccessful();
    }

    public function test_custom_permission_options_are_present(): void
    {
        $options = RoleResource::getCustomPermissionOptions();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('delete-backup', $options);
        $this->assertArrayHasKey('download-backup', $options);
    }

    public function test_cannot_render_create_page_without_permission(): void
    {
        $user = User::factory()->create(); // User without 'create_role' permission
        $this->actingAs($user);

        $this->get(RoleResource::getUrl('create'))->assertForbidden();
    }

    public function test_cannot_render_edit_page_without_permission(): void
    {
        $user = User::factory()->create(); // User without 'update_role' permission
        $this->actingAs($user);
        $role = Role::factory()->create(['name' => 'test role']);

        $this->get(RoleResource::getUrl('edit', ['record' => $role]))->assertForbidden();
    }
}
