<?php

namespace Tests\Feature\Models;

use App\Models\Export;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public'); // Fake the 'public' disk for filesystem interactions
    }

    public function test_deletes_directory_on_export_deletion_void(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        // Create a real Export model instance.
        // We need to save it to the database for the 'deleted' event to fire correctly
        // when we call $export->delete().
        $export = Export::factory()->create([
            'file_disk' => 'public', // Ensure it uses the faked disk
        ]);

        // The directory path is based on the model's key (ID)
        $directoryPath = 'filament_exports'.DIRECTORY_SEPARATOR.$export->id;

        // Ensure the directory "exists" on the faked disk before deletion
        Storage::disk('public')->makeDirectory($directoryPath);
        $this->assertTrue(Storage::disk('public')->exists($directoryPath), "The directory {$directoryPath} should exist.");

        // Act: Delete the export model
        // This will trigger the 'deleted' event, which our listener will catch
        $export->delete();

        // Assert: The directory should no longer exist on the faked disk
        $this->assertFalse(Storage::disk('public')->exists($directoryPath), "The directory {$directoryPath} should be missing.");
    }

    public function test_does_not_delete_directory_if_not_exists_void(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        // Create a real Export model instance
        $export = Export::factory()->create([
            'file_disk' => 'public',
        ]);

        $directoryPath = 'filament_exports'.DIRECTORY_SEPARATOR.$export->id;

        // Assert that the directory does NOT exist initially
        $this->assertFalse(Storage::disk('public')->exists($directoryPath), "The directory {$directoryPath} should be missing.");

        // Act: Delete the export model
        // The listener should check for existence before attempting deletion
        $export->delete();

        // Assert: The directory should still not exist (no change, no error)
        $this->assertFalse(Storage::disk('public')->exists($directoryPath), "The directory {$directoryPath} should be missing.");
    }

    public function test_creator_id_is_set_on_creation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $export = Export::factory()->create();

        $this->assertEquals($user->id, $export->creator_id);
    }

    public function test_create_export_throws_exception_when_not_authenticated(): void
    {
        $this->expectException(\RuntimeException::class);
        Export::factory()->create();
    }
}
