<?php

namespace Tests\Feature\Filament\Imports;

use App\Enums\Page\Status;
use App\Filament\Imports\PageImporter;
use App\Models\Import;
use App\Models\Page;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_a_new_page_when_id_is_not_present_in_data(): void
    {
        // Arrange
        $user = User::factory()->create(); // Create a user for creator_id
        $this->actingAs($user);

        $initialPageCount = Page::count(); // Get the initial count of pages

        $data = [
            'title' => 'Test Page',
            'content' => 'Test Content',
            'status' => 'draft',
            'creator_id' => $user->id, // Use the created user's ID
        ];

        // Act
        $import = Import::factory()->create([
            'total_rows' => 0,
            'successful_rows' => 0,
        ]);

        $columnMap = [
            'title' => 'title',
            'content' => 'content',
            'status' => 'status',
            'creator_id' => 'creator_id',
        ];
        $importer = new PageImporter($import, $columnMap, []);
        $importer($data); // Call __invoke to set the internal $data property
        $page = $importer->getRecord();

        // Assert
        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals($initialPageCount + 1, Page::count()); // Assert one new page was created

        $this->assertEquals($data['title'], $page->title);
        $this->assertEquals($data['content'], $page->content);
        $this->assertEquals('draft', $page->status->value);

        // Optionally, assert that the page exists in the database
        $this->assertDatabaseHas('pages', [
            'status' => 'draft',
            'creator_id' => $user->id,
            'id' => $page->id,
        ]);

        // Retrieve the page from the database to ensure Laravel's casts are applied
        $retrievedPage = Page::find($page->id); // Assuming App\Models\Page is your model namespace

        // Assert the JSON fields by comparing the PHP arrays
        $this->assertEquals($data['title'], $retrievedPage?->title);
        $this->assertEquals($data['content'], $retrievedPage?->content);
    }

    public function test_can_update_an_existing_page_when_id_is_present_and_matches(): void
    {
        // Arrange
        $user = User::factory()->create(); // Create a user for creator_id
        $this->actingAs($user);

        $existingPage = Page::factory()->create();
        $initialPageCount = Page::count(); // Get the initial count of pages

        $data = [
            'id' => $existingPage->id,
            'title' => 'Updated Title',
            'content' => 'Updated Content',
            'status' => 'publish',
            'creator_id' => $user->id, // Use the created user's ID
        ];

        // Act
        $import = Import::factory()->create([
            'total_rows' => 0,
            'successful_rows' => 0,
        ]);

        $columnMap = [
            'id' => 'id',
            'title' => 'title',
            'content' => 'content',
            'status' => 'status',
            'creator_id' => 'creator_id',
        ];
        $importer = new PageImporter($import, $columnMap, []);
        $importer($data); // Call __invoke to set the internal $data property
        $page = $importer->getRecord();

        // Assert
        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals($initialPageCount, Page::count()); // Assert no new page was created, only updated
        $this->assertEquals($data['title'], $page->title);
        $this->assertEquals($data['content'], $page->content);
        $this->assertEquals('publish', $page->status->value);

        // Optionally, assert that the page exists in the database
        $this->assertDatabaseHas('pages', [
            'id' => $existingPage->id,
            'status' => 'publish',
            'creator_id' => $user->id,
        ]);

        // Retrieve the page from the database to ensure Laravel's casts are applied
        $retrievedPage = Page::find($page->id); // Assuming App\Models\Page is your model namespace

        // Assert the JSON fields by comparing the PHP arrays
        $this->assertEquals($data['title'], $retrievedPage?->title);
        $this->assertEquals($data['content'], $retrievedPage?->content);
    }

    public function test_handles_creator_id_when_present_in_import_data_and_user_has_view_all_permission(): void
    {
        // Arrange
        $user = User::factory()->create();
        Permission::firstOrCreate(['name' => 'view_all_page']);
        $user->givePermissionTo('view_all_page');
        $this->actingAs($user);

        $otherUser = User::factory()->create();

        $data = [
            'creator_id' => $otherUser->id, // Different creator ID
            'title' => 'Test Page',
            'content' => 'Test Content',
            'status' => 'draft',
        ];

        // Act
        $import = Import::factory()->create([
            'total_rows' => 0,
            'successful_rows' => 0,
        ]);

        $columnMap = [
            'title' => 'title',
            'content' => 'content',
            'status' => 'status',
            'creator_id' => 'creator_id',
        ];
        $importer = new PageImporter($import, $columnMap, []);
        $importer($data);
        /** @var Page */
        $page = $importer->getRecord();

        // Assert
        $this->assertEquals($otherUser->id, $page->creator_id);
    }

    public function test_handles_creator_id_when_present_in_import_data_and_user_does_not_have_view_all_permission(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $otherUser = User::factory()->create();

        $data = [
            'creator_id' => $otherUser->id, // Different creator ID
            'title' => 'Test Page',
            'content' => 'Test Content',
            'status' => 'draft',
        ];

        // Act
        $import = Import::factory()->create([
            'total_rows' => 0,
            'successful_rows' => 0,
        ]);

        $columnMap = [
            'title' => 'title',
            'content' => 'content',
            'status' => 'status',
            'creator_id' => 'creator_id',
        ];
        $importer = new PageImporter($import, $columnMap, []);
        $importer($data);
        /** @var Page */
        $page = $importer->getRecord();

        // Assert
        $this->assertEquals($user->id, $page->creator_id);
    }

    public function test_handles_creator_id_when_absent_in_import_data(): void
    {
        // Arrange
        $currentUser = User::factory()->create();
        $this->actingAs($currentUser);
        $data = [
            'title' => 'Test Page',
            'content' => 'Test Content',
            'status' => 'draft',
        ];

        // Act
        $import = Import::factory()->create([
            'total_rows' => 0,
            'successful_rows' => 0,
        ]);
        $columnMap = [
            'title' => 'title',
            'content' => 'content',
            'status' => 'status',
        ];
        $importer = new PageImporter($import, $columnMap, []);
        $importer($data);
        /** @var Page */
        $page = $importer->getRecord();

        // Assert
        $this->assertEquals($currentUser->id, $page->creator_id);
    }

    public function test_handles_status_enum_during_import(): void
    {
        // Arrange
        $this->actingAs(User::factory()->create());
        $data = [
            'title' => 'Test Page',
            'content' => 'Test Content',
            'status' => 'publish',
        ];

        // Act
        $import = Import::factory()->create([
            'total_rows' => 0,
            'successful_rows' => 0,
        ]);
        $columnMap = [
            'title' => 'title',
            'content' => 'content',
            'status' => 'status',
        ];
        $importer = new PageImporter($import, $columnMap, []);
        $importer($data);
        /** @var Page */
        $page = $importer->getRecord();

        // Assert
        $this->assertEquals(Status::Publish, $page->status);
    }
}
