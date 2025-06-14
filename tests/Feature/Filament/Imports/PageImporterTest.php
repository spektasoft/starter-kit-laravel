<?php

namespace Tests\Feature\Filament\Imports;

use App\Enums\Page\Status;
use App\Filament\Imports\PageImporter;
use App\Models\Import;
use App\Models\Page;
use App\Models\Permission;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ValueError;

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
            'title' => ['en' => 'Test Page'],
            'content' => ['en' => 'Test Content'],
            'status' => 'draft',
            'creator_id' => $user->id, // Use the created user's ID
        ];

        // Act
        $import = Import::factory()->create([
            'total_rows' => 0,
            'successful_rows' => 0,
            'user_id' => $user->id,
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

        $this->assertEquals($data['title']['en'], $page->title);
        $this->assertEquals($data['content']['en'], $page->content);
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
        $this->assertEquals($data['title']['en'], $retrievedPage?->title);
        $this->assertEquals($data['content']['en'], $retrievedPage?->content);
    }

    public function test_can_update_an_existing_page_when_id_is_present_and_matches(): void
    {
        // Arrange
        $user = User::factory()->create(); // Create a user for creator_id
        $this->actingAs($user);

        $existingPage = Page::factory()->create(['creator_id' => $user->id]);
        $initialPageCount = Page::count();

        $data = [
            'id' => $existingPage->id,
            'title' => ['en' => 'Updated Title'],
            'content' => ['en' => 'Updated Content'],
            'status' => 'publish',
            'creator_id' => $user->id,
        ];

        // Act
        $import = Import::factory()->create([
            'total_rows' => 0,
            'successful_rows' => 0,
            'user_id' => $user->id,
        ]);

        $columnMap = [
            'id' => 'id',
            'title' => 'title',
            'content' => 'content',
            'status' => 'status',
            'creator_id' => 'creator_id',
        ];
        $importer = new PageImporter($import, $columnMap, []);
        $importer($data);
        $page = $importer->getRecord();

        // Assert
        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals($initialPageCount, Page::count());
        $this->assertEquals($data['title']['en'], $page->title);
        $this->assertEquals($data['content']['en'], $page->content);
        $this->assertEquals('publish', $page->status->value);
        $this->assertDatabaseHas('pages', [
            'id' => $existingPage->id,
            'status' => 'publish',
            'creator_id' => $user->id,
        ]);

        $retrievedPage = Page::find($page->id);
        $this->assertEquals($data['title']['en'], $retrievedPage?->title);
        $this->assertEquals($data['content']['en'], $retrievedPage?->content);
    }

    public function test_cannot_update_creator_id_when_user_owns_page(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $existingPage = Page::factory()->create(['creator_id' => $user->id]);
        $originalCreatorId = $existingPage->creator_id;

        $data = [
            'id' => $existingPage->id,
            'title' => ['en' => 'Updated Title'],
            'content' => ['en' => 'Updated Content'],
            'status' => 'publish',
            'creator_id' => User::factory()->create()->id, // Attempt to change the creator_id
        ];

        // Act
        $import = Import::factory()->create([
            'total_rows' => 0,
            'successful_rows' => 0,
            'user_id' => $user->id,
        ]);

        $columnMap = [
            'id' => 'id',
            'title' => 'title',
            'content' => 'content',
            'status' => 'status',
            'creator_id' => 'creator_id',
        ];

        $importer = new PageImporter($import, $columnMap, []);
        $importer($data);
        /** @var ?Page */
        $page = $importer->getRecord();

        // Assert
        $this->assertEquals($originalCreatorId, $page?->creator_id);

        $this->assertDatabaseHas('pages', [
            'id' => $existingPage->id,
            'creator_id' => $originalCreatorId,
        ]);
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
            'title' => ['en' => 'Test Page'],
            'content' => ['en' => 'Test Content'],
            'status' => 'draft',
        ];

        // Act
        $import = Import::factory()->create([
            'total_rows' => 0,
            'successful_rows' => 0,
            'user_id' => $user->id,
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
            'title' => ['en' => 'Test Page'],
            'content' => ['en' => 'Test Content'],
            'status' => 'draft',
        ];

        // Act
        $import = Import::factory()->create([
            'total_rows' => 0,
            'successful_rows' => 0,
            'user_id' => $user->id,
        ]);

        $columnMap = [
            'title' => 'title',
            'content' => 'content',
            'status' => 'status',
            'creator_id' => 'creator_id',
        ];
        $importer = new PageImporter($import, $columnMap, []);
        $importer($data);
        /** @var ?Page */
        $page = $importer->getRecord();

        // Assert
        $this->assertNull($page);
    }

    public function test_handles_creator_id_when_absent_in_import_data(): void
    {
        // Arrange
        $currentUser = User::factory()->create();
        $this->actingAs($currentUser);
        $data = [
            'title' => ['en' => 'Test Page'],
            'content' => ['en' => 'Test Content'],
            'status' => 'draft',
        ];

        // Act
        $import = Import::factory()->create([
            'total_rows' => 0,
            'successful_rows' => 0,
            'user_id' => $currentUser->id,
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
            'title' => ['en' => 'Test Page'],
            'content' => ['en' => 'Test Content'],
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

    public function test_authorization_failure_when_updating_page_without_ownership_or_view_all_permission(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $otherUser = User::factory()->create();
        $existingPage = Page::factory()->create(['creator_id' => $otherUser->id]);

        $data = [
            'id' => $existingPage->id,
            'title' => ['en' => 'Updated Title'],
            'content' => ['en' => 'Updated Content'],
            'status' => 'publish',
            'creator_id' => $otherUser->id,
        ];

        // Act
        $import = Import::factory()->create([
            'total_rows' => 0,
            'successful_rows' => 0,
            'user_id' => $user->id,
        ]);

        $columnMap = [
            'id' => 'id',
            'title' => 'title',
            'content' => 'content',
            'status' => 'status',
            'creator_id' => 'creator_id',
        ];
        $importer = new PageImporter($import, $columnMap, []);
        $importer($data);
        $page = $importer->getRecord();

        // Assert
        $this->assertNull($page);
    }

    public function test_invalid_status_value_results_in_failed_import_row(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $data = [
            'title' => ['en' => 'Test Page'],
            'content' => ['en' => 'Test Content'],
            'status' => 'pending', // Invalid status
            'creator_id' => $user->id,
        ];

        // Act
        $import = Import::factory()->create([
            'total_rows' => 1,
            'successful_rows' => 0,
            'user_id' => $user->id,
        ]);

        $columnMap = [
            'title' => 'title',
            'content' => 'content',
            'status' => 'status',
            'creator_id' => 'creator_id',
        ];
        $importer = new PageImporter($import, $columnMap, []);
        try {
            $importer($data);
        } catch (ValueError $e) {
            $page = $importer->getRecord();

            // Assert
            $this->assertNull($page);

            return;
        }

        $this->fail('Expected exception was not thrown.');
    }

    public function test_invalid_json_string_for_title_fails_the_row(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $data = [
            'title' => 'invalid json',
            'content' => ['en' => 'Test Content'],
            'status' => 'draft',
            'creator_id' => $user->id,
        ];

        // Act
        $import = Import::factory()->create([
            'total_rows' => 1,
            'successful_rows' => 0,
            'user_id' => $user->id,
        ]);

        $columnMap = [
            'title' => 'title',
            'content' => 'content',
            'status' => 'status',
            'creator_id' => 'creator_id',
        ];
        $importer = new PageImporter($import, $columnMap, []);
        try {
            $importer($data);
        } catch (Exception $e) {
            $page = $importer->getRecord();

            // Assert
            $this->assertNull($page);

            return;
        }

        $this->fail('Expected exception was not thrown.');
    }

    public function test_invalid_creator_id_fails_the_row(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $data = [
            'title' => ['en' => 'Test Page'],
            'content' => ['en' => 'Test Content'],
            'status' => 'draft',
            'creator_id' => 9999, // Non-existent user ID
        ];

        // Act
        $import = Import::factory()->create([
            'total_rows' => 1,
            'successful_rows' => 0,
            'user_id' => $user->id,
        ]);

        $columnMap = [
            'title' => 'title',
            'content' => 'content',
            'status' => 'status',
            'creator_id' => 'creator_id',
        ];
        $importer = new PageImporter($import, $columnMap, []);
        $importer($data);
        $page = $importer->getRecord();

        // Assert
        $this->assertNull($page);
    }

    public function test_handles_null_creator_id_bug(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        /** @var ?Page */
        $existingPage = null;

        try {
            $existingPage = Page::factory()->create(['creator_id' => null]);
        } catch (Exception $e) {
            $this->assertNull($existingPage);

            return;
        }

        $this->fail('Expected exception was not thrown.');
    }

    public function test_update_non_existent_record_creates_new_record(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $initialPageCount = Page::count();

        $data = [
            'id' => '9999', // Non-existent ID
            'title' => ['en' => 'Test Page'],
            'content' => ['en' => 'Test Content'],
            'status' => 'draft',
            'creator_id' => $user->id,
        ];

        // Act
        $import = Import::factory()->create([
            'total_rows' => 0,
            'successful_rows' => 0,
            'user_id' => $user->id,
        ]);

        $columnMap = [
            'id' => 'id',
            'title' => 'title',
            'content' => 'content',
            'status' => 'status',
            'creator_id' => 'creator_id',
        ];
        $importer = new PageImporter($import, $columnMap, []);
        $importer($data);
        $page = $importer->getRecord();

        // Assert
        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals($initialPageCount + 1, Page::count());
    }
}
