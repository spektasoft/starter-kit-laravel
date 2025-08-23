<?php

namespace Tests\Feature\Filament\Resources;

use App\Enums\Page\Status;
use App\Filament\Resources\PageResource;
use App\Filament\Resources\PageResource\Pages\CreatePage;
use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Filament\Resources\PageResource\Pages\ListPages;
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

        config()->set('app.supported_locales', ['en', 'es', 'fr']);
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
        $this->assertEquals('<p>'.$newData->content.'</p>', $retrievedPage?->content);
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
        $this->assertEquals('<p>'.$newData->content.'</p>', $updatedPage?->content);
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

    public function test_malicious_html_content_is_sanitized_on_save(): void
    {
        $maliciousContent = '<p>Test</p><script>alert("xss");</script>';
        $sanitizedContent = '<p>Test</p>';

        // Create the page with malicious content
        /** @var Testable */
        $livewire = Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => ['en' => 'XSS Test'],
                'content' => ['en' => $maliciousContent],
                'status' => Status::Draft->value,
            ]);

        $livewire->assertHasNoFormErrors();
        $livewire->call('create');

        // Assert the content stored in the database is sanitized
        $this->assertDatabaseHas('pages', [
            'content->en' => $sanitizedContent,
        ]);
    }

    public function test_title_column_is_searchable_across_locales(): void
    {
        $pageEn = Page::factory()->create(['title' => ['en' => 'English Title', 'es' => 'Titulo Español']]);
        $pageEs = Page::factory()->create(['title' => ['en' => 'Another English', 'es' => 'Otro Titulo']]);
        $pageFr = Page::factory()->create(['title' => ['en' => 'French Page', 'fr' => 'Page Française']]);

        $livewire = Livewire::test(ListPages::class);
        $livewire->assertCanSeeTableRecords([$pageEn, $pageEs, $pageFr]);
        $livewire->searchTable('English');
        $livewire->assertCanSeeTableRecords([$pageEn, $pageEs]);
        $livewire->assertCanNotSeeTableRecords([$pageFr]);
        $livewire->searchTable('Titulo');
        $livewire->assertCanSeeTableRecords([$pageEn, $pageEs]);
        $livewire->assertCanNotSeeTableRecords([$pageFr]);
        $livewire->searchTable('French');
        $livewire->assertCanSeeTableRecords([$pageFr]);
        $livewire->assertCanNotSeeTableRecords([$pageEn, $pageEs]);
    }

    public function test_locale_tabs_are_ordered_correctly_on_edit_page(): void
    {
        app()->setLocale('es');

        $page = Page::factory()->create([
            'title' => [
                'en' => 'English Title',
                'es' => 'Spanish Title',
                'fr' => 'French Title',
            ],
            'content' => [
                'en' => 'English Content',
                'es' => 'Spanish Content',
                'fr' => 'French Content',
            ],
        ]);

        $livewire = Livewire::test(EditPage::class, [
            'record' => $page->id,
        ]);

        // The expected order is 'es' (current locale with content), then 'en' and 'fr' (other locales with content)
        $livewire->assertSeeInOrder(['es', 'en', 'fr']);

        // Assert the values of individual locale fields
        $livewire->assertSet('data.title.es', 'Spanish Title');
        $livewire->assertSet('data.title.en', 'English Title');
        $livewire->assertSet('data.title.fr', 'French Title');
    }

    public function test_locale_tabs_are_empty_on_create_page(): void
    {
        app()->setLocale('es');

        $livewire = Livewire::test(CreatePage::class);

        // The expected order is 'es' (current locale), then 'en' and 'fr' (other configured locales)
        $livewire->assertSeeInOrder(['es', 'en', 'fr']);

        // Assert the values of individual locale fields are empty
        $livewire->assertSet('data.title.es', null);
        $livewire->assertSet('data.title.en', null);
        $livewire->assertSet('data.title.fr', null);
    }
}
