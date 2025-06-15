<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\PermissionResource;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->actingAs($user);

        Permission::firstOrCreate(['name' => 'view_any_permission']);
        $user->givePermissionTo('view_any_permission');

    }

    public function test_permission_list_page_can_be_rendered(): void
    {
        $this->get(PermissionResource::getUrl('index'))->assertSuccessful();
    }

    public function test_permission_list_page_cannot_be_rendered_without_permission(): void
    {
        $this->actingAs(User::factory()->create()); // A user without the 'view_any_permission'
        $this->get(PermissionResource::getUrl('index'))->assertForbidden();
    }
}
