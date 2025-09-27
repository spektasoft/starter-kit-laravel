<?php

namespace Tests\Feature\Observers;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Tests\TestCase;

class MediaObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_associates_the_authenticated_user_as_creator_on_creating(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $media = Media::factory()->create(['creator_id' => null]);

        $this->assertEquals($user->id, $media->creator_id);
    }

    public function test_converts_image_to_webp_on_created(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $originalPath = 'test.png';
        $image = Image::canvas(100, 100, 'ffffff');
        Storage::disk('public')->put($originalPath, $image->stream('png'));

        $media = Media::create([
            'name' => 'Test Image',
            'path' => $originalPath,
            'disk' => 'public',
            'size' => 1024,
            'type' => 'image/png',
            'ext' => 'png',
        ]);

        $expectedWebpPath = str_replace(pathinfo($media->path, PATHINFO_EXTENSION), 'webp', $media->path);

        $this->assertTrue(Storage::disk('public')->exists($expectedWebpPath));
        $this->assertEquals($expectedWebpPath, $media->path);
        $this->assertFalse(Storage::disk('public')->exists($originalPath));
        $this->assertEquals('webp', $media->ext);
        $this->assertEquals('image/webp', $media->type);
    }

    public function test_does_not_convert_non_image_files_to_webp(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Storage::disk('public')->put('test.txt', 'This is a test file.');

        $media = Media::create([
            'name' => 'Test File',
            'path' => 'test.txt',
            'disk' => 'public',
            'size' => 20,
            'type' => 'text/plain',
            'ext' => 'txt',
        ]);

        $expectedWebpPath = str_replace(pathinfo($media->path, PATHINFO_EXTENSION), 'webp', $media->path);

        $this->assertFalse(Storage::disk('public')->exists($expectedWebpPath));
        $this->assertTrue(Storage::disk('public')->exists('test.txt'));
        $this->assertEquals('txt', $media->ext);
        $this->assertEquals('text/plain', $media->type);
    }

    public function test_removes_exif_data_on_created(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $originalPath = 'test.png';
        $image = Image::canvas(100, 100, 'ffffff');
        Storage::disk('public')->put($originalPath, $image->stream('png'));

        $media = Media::create([
            'name' => 'Test Image',
            'path' => $originalPath,
            'disk' => 'public',
            'size' => 1024,
            'type' => 'image/png',
            'ext' => 'png',
            'exif' => ['key' => 'value'],
        ]);

        $media->refresh();
        $this->assertNull($media->exif);
    }

    public function test_logs_an_error_if_webp_conversion_fails(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Image::shouldReceive('make')->andThrow(new \Exception('Conversion failed'));

        Media::factory()->create([
            'name' => 'Test Image',
            'path' => 'test.png',
            'disk' => 'public',
            'size' => 1024,
            'type' => 'image/png',
            'ext' => 'png',
        ]);

        $this->assertDatabaseHas('media', ['name' => 'Test Image']);
    }
}
