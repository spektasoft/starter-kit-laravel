<?php

namespace Tests\Feature\Policies;

use App\Models\Permission;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var UserPolicy
     */
    private $policy;

    public static function setUpPermissions(): void
    {
        $permissionNames = [
            'view_any_user',
            'view_user',
            'view_all_user',
            'create_user',
            'update_user',
            'delete_user',
            'delete_any_user',
            'force_delete_user',
            'force_delete_any_user',
            'restore_user',
            'restore_any_user',
            'replicate_user',
            'reorder_user',
        ];

        $permissions = collect($permissionNames)->map(function ($permissionName) {
            return [
                'id' => strtolower((string) Str::ulid()),
                'name' => $permissionName,
                'guard_name' => 'web',
            ];
        });

        Permission::insert($permissions->toArray());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy;

        static::setUpPermissions();
    }

    public function test_view_any_grants_access_to_users_with_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view_any_user');

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_view_any_denies_access_to_users_without_permission(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->policy->viewAny($user));
    }

    public function test_view_grants_access_to_users_with_permission(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->givePermissionTo('view_user');

        $this->assertTrue($this->policy->view($user, $otherUser));
    }

    public function test_view_denies_access_to_users_without_permission(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse($this->policy->view($user, $otherUser));
    }

    public function test_create_grants_access_to_users_with_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create_user');

        $this->assertTrue($this->policy->create($user));
    }

    public function test_create_denies_access_to_users_without_permission(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->policy->create($user));
    }

    public function test_update_grants_access_to_users_with_permission(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->givePermissionTo('update_user');

        $this->assertTrue($this->policy->update($user, $otherUser));
    }

    public function test_update_denies_access_to_users_without_permission(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse($this->policy->update($user, $otherUser));
    }

    public function test_delete_grants_access_to_users_with_permission(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->givePermissionTo('delete_user');

        $this->assertTrue($this->policy->delete($user, $otherUser));
    }

    public function test_delete_denies_access_to_users_without_permission(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse($this->policy->delete($user, $otherUser));
    }

    public function test_delete_any_grants_access_to_users_with_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('delete_any_user');

        $this->assertTrue($this->policy->deleteAny($user));
    }

    public function test_delete_any_denies_access_to_users_without_permission(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->policy->deleteAny($user));
    }
}
