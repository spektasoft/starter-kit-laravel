<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\PageResource;
use App\Filament\Resources\PageResource\Pages\CreatePage;
use App\Models\Page;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class PageResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->actingAs($user);
        Permission::firstOrCreate(['name' => 'view_all_page']);
        $user->givePermissionTo('view_all_page');
        Permission::firstOrCreate(['name' => 'view_any_page']);
        $user->givePermissionTo('view_any_page');
        Permission::firstOrCreate(['name' => 'create_page']);
        $user->givePermissionTo('create_page');
        Permission::firstOrCreate(['name' => 'update_page']);
        $user->givePermissionTo('update_page');
        Permission::firstOrCreate(['name' => 'delete_page']);
        $user->givePermissionTo('delete_page');
    }

    public function test_cannot_render_create_page_without_permission(): void
    {
        $user = User::factory()->create(); // User without 'create_page' permission
        $this->actingAs($user);

        $this->get(PageResource::getUrl('create'))->assertForbidden();
    }

    public function test_cannot_render_edit_page_without_permission(): void
    {
        $user = User::factory()->create(); // User without 'update_page' permission
        $this->actingAs($user);
        $page = Page::factory()->create(['creator_id' => $user->id]);

        $this->get(PageResource::getUrl('edit', ['record' => $page]))->assertForbidden();
    }

    public function test_cannot_delete_page_without_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user); // User without 'delete_page' permission
        $user->givePermissionTo('view_any_page');
        $page = Page::factory()->create();

        $listPages = Livewire::test(PageResource\Pages\ListPages::class);
        $listPages->assertTableActionHidden('delete', $page);
    }

    public function test_cannot_bulk_delete_pages_without_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user); // User without 'delete_page' permission
        $user->givePermissionTo('view_any_page');
        $pages = Page::factory(3)->create();

        $listPages = Livewire::test(PageResource\Pages\ListPages::class);
        $listPages->callTableBulkAction('delete', $pages->pluck('id')->toArray());

        $this->assertEquals(Page::count(), 3);
    }

    public function test_list_pages_page_can_be_rendered(): void
    {
        $this->get(PageResource::getUrl('index'))->assertSuccessful();
    }

    public function test_create_page_page_can_be_rendered(): void
    {
        $this->get(PageResource::getUrl('create'))->assertSuccessful();
    }

    public function test_edit_page_page_can_be_rendered(): void
    {
        $page = Page::factory()->create();
        $this->get(PageResource::getUrl('edit', ['record' => $page]))->assertSuccessful();
    }

    public function test_can_create_new_pages(): void
    {
        $newData = Page::factory()->make();

        /** @var Testable */
        $livewire = Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => [
                    'en' => $newData->title,
                ],
                'content' => [
                    'en' => $newData->content,
                ],
                'status' => $newData->status,
            ]);

        $livewire->assertHasNoFormErrors();
        $livewire->call('create');

        $this->assertDatabaseHas(Page::class, [
            'status' => $newData->status->value,
        ]);

        // Retrieve the page from the database
        $retrievedPage = Page::first();

        // Assert the JSON fields by comparing the PHP arrays
        $this->assertEquals($newData->title, $retrievedPage?->title);
        $this->assertEquals($newData->content, $retrievedPage?->content);
    }

    public function test_can_edit_pages(): void
    {
        $page = Page::factory()->create();
        $newData = Page::factory()->make();

        $livewire = Livewire::test(PageResource\Pages\EditPage::class, [
            'record' => $page->id,
        ]);
        $livewire->fillForm([
            'title' => [
                'en' => $newData->title,
            ],
            'content' => [
                'en' => $newData->content,
            ],
            'status' => $newData->status,
        ]);
        $livewire->assertHasNoFormErrors();
        $livewire->call('save');

        $this->assertDatabaseHas(Page::class, [
            'id' => $page->getKey(),
            'status' => $newData->status->value,
        ]);

        /** @var ?Page */
        $updatedPage = Page::find($page->getKey());
        $this->assertEquals($newData->title, $updatedPage?->title);
        $this->assertEquals($newData->content, $updatedPage?->content);
    }

    public function test_can_delete_page(): void
    {
        $page = Page::factory()->create();

        $listPages = Livewire::test(PageResource\Pages\ListPages::class);
        $listPages->callTableAction('delete', $page);
        $listPages->assertSuccessful();

        $this->assertDatabaseMissing(Page::class, ['id' => $page->getKey()]);
    }

    public function test_can_bulk_delete_pages(): void
    {
        $pages = Page::factory(3)->create();

        $listPages = Livewire::test(PageResource\Pages\ListPages::class);
        $listPages->callTableBulkAction('delete', $pages->pluck('id')->toArray());
        $listPages->assertSuccessful();

        foreach ($pages as $page) {
            $this->assertDatabaseMissing(Page::class, ['id' => $page->getKey()]);
        }
    }
}
