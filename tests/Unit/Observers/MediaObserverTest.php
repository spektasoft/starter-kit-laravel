<?php

namespace Tests\Unit\Observers;

use App\Models\Media;
use App\Models\User;
use Awcodes\Curator\PathGenerators\UserPathGenerator;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
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

    public function test_does_not_move_file_when_creator_id_is_unchanged(): void
    {
        // 1. Arrange
        $user = User::factory()->create();

        $originalDir = 'media/'.$user->id;
        $filename = 'test-image.jpg';
        $originalPath = $originalDir.'/'.$filename;
        Storage::disk('public')->put($originalPath, 'dummy content');

        $media = Media::factory()->create([
            'creator_id' => $user->id,
            'disk' => 'public',
            'directory' => $originalDir,
            'path' => $originalPath,
        ]);

        // 2. Act
        // Update a different attribute, creator_id remains unchanged
        $media->title = 'New Title';
        $media->save();

        // 3. Assert
        // Assert the file still exists in the original location
        $this->assertTrue(Storage::disk('public')->exists($originalPath));
        // Assert the database record's path and directory are unchanged
        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'path' => $originalPath,
            'directory' => $originalDir,
            'creator_id' => $user->id,
            'title' => 'New Title',
        ]);
    }

    public function test_does_not_move_file_when_path_generator_is_not_user_path_generator(): void
    {
        // 1. Arrange
        // Set a different path generator
        config(['curator.path_generator' => \Awcodes\Curator\PathGenerators\DatePathGenerator::class]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $originalDir = 'media/'.$user1->id;
        $filename = 'test-image.jpg';
        $originalPath = $originalDir.'/'.$filename;
        Storage::disk('public')->put($originalPath, 'dummy content');

        $media = Media::factory()->create([
            'creator_id' => $user1->id,
            'disk' => 'public',
            'directory' => $originalDir,
            'path' => $originalPath,
        ]);

        // 2. Act
        $media->creator_id = $user2->id;
        $media->save();

        // 3. Assert
        // Assert the file still exists in the original location
        $this->assertTrue(Storage::disk('public')->exists($originalPath));
        // Assert the database record's path and directory are unchanged, but creator_id is updated
        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'path' => $originalPath,
            'directory' => $originalDir,
            'creator_id' => $user2->id, // creator_id should still be updated in DB
        ]);
    }

    public function test_does_not_move_file_and_logs_warning_when_source_file_does_not_exist(): void
    {
        // 1. Arrange
        Log::shouldReceive('warning')
            ->once()
            ->with(Mockery::pattern('/^MediaObserver: Source file not found at/'));

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $originalDir = 'media/'.$user1->id;
        $filename = 'test-image.jpg';
        $originalPath = $originalDir.'/'.$filename;
        // DO NOT put the file in storage, simulate it missing

        $media = Media::factory()->create([
            'creator_id' => $user1->id,
            'disk' => 'public',
            'directory' => $originalDir,
            'path' => $originalPath,
        ]);

        // 2. Act
        $media->creator_id = $user2->id;
        $media->save();

        // 3. Assert
        // Assert that no file was moved (since it didn't exist)
        $this->assertFalse(Storage::disk('public')->exists($originalPath));
        $newDir = 'media/'.$user2->id;
        $newPath = $newDir.'/'.$filename;
        $this->assertFalse(Storage::disk('public')->exists($newPath));

        // Assert the database record's path and directory are unchanged, but creator_id is updated
        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'path' => $originalPath, // Path should remain the old one in DB
            'directory' => $originalDir, // Directory should remain the old one in DB
            'creator_id' => $user2->id, // creator_id should still be updated in DB
        ]);
    }

    public function test_rolls_back_transaction_when_file_move_fails(): void
    {
        // 1. Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $originalDir = 'media/'.$user1->id;
        $filename = 'test-image.jpg';
        $originalPath = $originalDir.'/'.$filename;
        Storage::disk('public')->put($originalPath, 'dummy content');

        $media = Media::factory()->create([
            'creator_id' => $user1->id,
            'disk' => 'public',
            'directory' => $originalDir,
            'path' => $originalPath,
        ]);

        $newDir = 'media/'.$user2->id;
        $newPath = $newDir.'/'.$filename;

        // Mock the Storage facade
        $mockDisk = Mockery::mock(Filesystem::class);

        // Expect the 'disk' method to be called on Storage, returning our mockDisk
        Storage::shouldReceive('disk')
            ->with('public') // Assuming 'public' disk is used
            ->andReturn($mockDisk);

        // Allow makeDirectory to be called on the mock disk
        /** @var \Mockery\ExpectationInterface $expectation */
        $expectation = $mockDisk->shouldReceive('makeDirectory');
        $expectation->andReturn(true);

        // On our mockDisk, expect 'move' to be called and throw an exception
        /** @var \Mockery\Expectation $expectationMove */
        $expectationMove = $mockDisk->shouldReceive('move');
        $higherOrderMessage = $expectationMove->once();
        $higherOrderMessage->andThrow(new \Exception('Simulated file move failure'));

        // Also, on our mockDisk, expect 'exists' to be called and return true for the original path
        // and false for the new path (after rollback)
        /** @var \Mockery\Expectation $expectationExists */
        $expectationExists = $mockDisk->shouldReceive('exists');
        $withExpectation = $expectationExists->with($originalPath);
        $withExpectation->andReturn(true);

        /** @var \Mockery\Expectation $expectationExists2 */
        $expectationExists2 = $mockDisk->shouldReceive('exists');
        $withExpectation2 = $expectationExists2->with($newPath);
        $withExpectation2->andReturn(false);

        // 2. Act & Assert
        // Expect an exception to be thrown
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Simulated file move failure');

        try {
            $media->creator_id = $user2->id;
            $media->save();
        } catch (\Exception $e) {
            // Assert that the original file still exists (transaction rolled back)
            $this->assertTrue(Storage::disk('public')->exists($originalPath));
            $media->refresh();
            // Assert that the new file does not exist
            $this->assertFalse(Storage::disk('public')->exists($newPath));

            // Assert the database record's path, directory, and creator_id are unchanged
            $this->assertDatabaseHas('media', [
                'id' => $media->id,
                'path' => $originalPath,
                'directory' => $originalDir,
                'creator_id' => $user1->id, // Should be original creator_id
            ]);
            throw $e; // Re-throw the exception to satisfy expectException
        }
    }

    public function test_assigns_authenticated_user_as_creator_when_creating_media_without_one(): void
    {
        // 1. Arrange
        $user = User::factory()->create();
        $this->actingAs($user); // Authenticate the user

        // 2. Act
        // The factory will trigger the 'creating' event
        $media = Media::factory()->create([
            'creator_id' => null, // Explicitly create without a creator
        ]);

        // 3. Assert
        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'creator_id' => $user->id,
        ]);
    }

    public function test_does_not_overwrite_existing_creator_when_creating_media(): void
    {
        // 1. Arrange
        $originalCreator = User::factory()->create();
        $loggedInUser = User::factory()->create();
        $this->actingAs($loggedInUser);

        // 2. Act
        $media = Media::factory()->create([
            'creator_id' => $originalCreator->id,
        ]);

        // 3. Assert
        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'creator_id' => $originalCreator->id, // Assert it's the original creator
        ]);
    }

    public function test_throws_exception_when_creating_media_without_authentication(): void
    {
        // 1. Arrange
        // Ensure no user is authenticated for this test.
        Auth::logout();

        // 2. Assert
        $this->expectException(\Illuminate\Auth\AuthenticationException::class);
        $this->expectExceptionMessage(
            'Cannot create Media without an authenticated user to assign as the creator.'
        );

        // 3. Act
        Media::factory()->create([
            'creator_id' => null,
        ]);
    }
}
