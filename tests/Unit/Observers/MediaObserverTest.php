<?php

namespace Tests\Unit\Observers;

use App\Models\Media;
use App\Models\User;
use Awcodes\Curator\PathGenerators\UserPathGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock the file system
        Storage::fake('public');
        // Set the config to use the UserPathGenerator for this test
        config(['curator.path_generator' => UserPathGenerator::class]);
        config(['curator.directory' => 'media']);
    }

    public function test_moves_the_file_and_updates_db_when_creator_id_is_changed(): void
    {
        // 1. Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create a dummy file in the original user's directory
        $originalDir = 'media/'.$user1->id;
        $filename = 'test-image.jpg';
        $originalPath = $originalDir.'/'.$filename;
        Storage::disk('public')->put($originalPath, 'dummy content');

        // Create a media record pointing to the original file
        $media = Media::factory()->create([
            'creator_id' => $user1->id,
            'disk' => 'public',
            'directory' => $originalDir,
            'path' => $originalPath,
        ]);

        $newDir = 'media/'.$user2->id;
        $newPath = $newDir.'/'.$filename;

        // 2. Act
        // Update the creator_id, which should trigger the observer's 'updated' method
        $media->creator_id = $user2->id;
        $media->save();

        // 3. Assert
        // Assert the original file no longer exists
        $this->assertFalse(Storage::disk('public')->exists($originalPath));
        // Assert the new file exists in the new location
        $this->assertTrue(Storage::disk('public')->exists($newPath));

        // Assert the database record has been updated
        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'path' => $newPath,
            'directory' => $newDir,
            'creator_id' => $user2->id,
        ]);
    }
}
