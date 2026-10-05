<?php

namespace Tests\Feature\Filament\Resources;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class UserResourceTestCase extends TestCase
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
}
