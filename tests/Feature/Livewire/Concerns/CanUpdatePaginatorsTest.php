<?php

namespace Tests\Feature\Livewire\Concerns;

use App\Filament\Resources\Media\Pages\ListMedia;
use App\Filament\Resources\Permissions\Pages\ListPermissions;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Livewire\Api\ApiTokenManage;
use App\Models\Media;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Features;
use Livewire\Component;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CanUpdatePaginatorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_token_manage_does_not_scroll_on_initial_render(): void
    {
        $testable = $this->mountApiTokenManageWithManyTokens();

        $testable->assertStatus(200)
            ->assertNotDispatched('scroll-to');
    }

    public function test_api_token_manage_scrolls_to_its_table_when_page_changes(): void
    {
        $testable = $this->mountApiTokenManageWithManyTokens();

        $this->assertPageChangeScrollsTo($testable, '#api-token-manage-table');
    }

    public function test_api_token_manage_scrolls_when_table_paginator_key_is_set(): void
    {
        $testable = $this->mountApiTokenManageWithManyTokens();
        $pageName = $this->tablePageName($testable);

        $testable->set('paginators.'.$pageName, 2);

        $testable->assertSet('paginators.'.$pageName, 2);
        $testable->assertHasNoErrors();
        $testable->assertStatus(200);
        $testable->assertDispatched('scroll-to', ['element' => '#api-token-manage-table']);
    }

    public function test_api_token_manage_scrolls_when_generic_page_key_is_set(): void
    {
        $testable = $this->mountApiTokenManageWithManyTokens();

        $testable->set('paginators.page', 2);

        $testable->assertHasNoErrors();
        $testable->assertDispatched('scroll-to', ['element' => '#api-token-manage-table']);
    }

    /**
     * @return Testable<Component>
     */
    private function mountApiTokenManageWithManyTokens(): Testable
    {
        if (! Features::hasApiFeatures()) {
            $this->markTestSkipped('API support is not enabled.');
        }

        /** @var User */
        $user = User::factory()->create();
        $this->actingAs($user);

        for ($i = 1; $i <= 30; $i++) {
            $user->createToken('Token '.$i);
        }

        return Livewire::test(ApiTokenManage::class);
    }

    public function test_list_users_scrolls_to_body_when_page_changes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Permission::firstOrCreate(['name' => 'view_any_user']);
        $user->givePermissionTo('view_any_user');
        User::factory()->count(30)->create();

        $this->assertPageChangeScrollsTo(Livewire::test(ListUsers::class), 'body');
    }

    public function test_list_permissions_scrolls_to_body_when_page_changes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Permission::firstOrCreate(['name' => 'view_any_permission']);
        $user->givePermissionTo('view_any_permission');

        for ($i = 1; $i <= 30; $i++) {
            Permission::firstOrCreate(['name' => 'paginator_permission_'.$i]);
        }

        $this->assertPageChangeScrollsTo(Livewire::test(ListPermissions::class), 'body');
    }

    public function test_list_media_scrolls_to_body_when_page_changes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Media::factory()->count(30)->create(['creator_id' => $user->id]);

        $this->assertPageChangeScrollsTo(Livewire::test(ListMedia::class), 'body');
    }

    /**
     * @template TComponent of Component
     *
     * @param  Testable<TComponent>  $testable
     */
    private function assertPageChangeScrollsTo(Testable $testable, string $element): void
    {
        $pageName = $this->tablePageName($testable);

        $testable->assertNotDispatched('scroll-to');

        $testable->call('gotoPage', 2, $pageName);

        $testable->assertSet('paginators.'.$pageName, 2);
        $testable->assertHasNoErrors();
        $testable->assertStatus(200);
        $testable->assertDispatched('scroll-to', ['element' => $element]);
    }

    /**
     * @template TComponent of Component
     *
     * @param  Testable<TComponent>  $testable
     */
    private function tablePageName(Testable $testable): string
    {
        $instance = $testable->instance();

        if (! method_exists($instance, 'getTablePaginationPageName')) {
            $this->fail('Component does not expose a table pagination page name.');
        }

        $pageName = $instance->getTablePaginationPageName();

        $this->assertIsString($pageName);

        return $pageName;
    }
}
