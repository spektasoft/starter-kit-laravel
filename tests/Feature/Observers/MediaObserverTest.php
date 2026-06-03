<?php

namespace Tests\Feature\Observers;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Mockery\Expectation;
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
        $manager = new ImageManager(new Driver);
        $image = $manager->create(100, 100)->fill('ffffff');
        $stream = (string) $image->toPng();
        Storage::disk('public')->put($originalPath, $stream);

        $media = Media::create([
            'name' => 'Test Image',
            'path' => $originalPath,
            'disk' => 'public',
            'size' => 1024,
            'type' => 'image/png',
            'ext' => 'png',
        ]);

        $expectedWebpPath = str_replace(pathinfo($originalPath, PATHINFO_EXTENSION), 'webp', $originalPath);

        $this->assertTrue(Storage::disk('public')->exists($expectedWebpPath));
        $this->assertEquals($expectedWebpPath, $media->path);
        $this->assertFalse(Storage::disk('public')->exists($originalPath));
        $this->assertEquals('webp', $media->ext);
        $this->assertEquals('image/webp', $media->type);
    }

    public function test_does_not_convert_webp_files_to_webp(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $webpPath = 'test.webp';
        $manager = new ImageManager(new Driver);
        $image = $manager->create(100, 100)->fill('ffffff');
        $stream = (string) $image->toWebp();
        Storage::disk('public')->put($webpPath, $stream);

        $this->assertNotNull($file = Storage::disk('public')->get($webpPath));
        $originalHash = md5($file);

        $media = Media::create([
            'name' => 'Test WebP Image',
            'path' => $webpPath,
            'disk' => 'public',
            'size' => 1024,
            'type' => 'image/webp',
            'ext' => 'webp',
        ]);

        $this->assertTrue(Storage::disk('public')->exists($webpPath));
        $this->assertEquals($webpPath, $media->path);
        $this->assertEquals('webp', $media->ext);
        $this->assertEquals('image/webp', $media->type);

        $this->assertEquals($originalHash, md5(Storage::disk('public')->get($webpPath)), 'The WebP file should remain unchanged after Media creation');
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
        $manager = new ImageManager(new Driver);
        $image = $manager->create(100, 100)->fill('ffffff');
        $stream = (string) $image->toPng();
        Storage::disk('public')->put($originalPath, $stream);

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

        // ImageManager is final — pass an instantiated object for a partial mock
        $realManager = new ImageManager(new Driver);
        $mockManager = \Mockery::mock($realManager);

        /** @var Expectation $expectation */
        $expectation = $mockManager->shouldReceive('read');
        $expectation->andThrow(new \Exception('Conversion failed'));

        $this->app->instance(ImageManager::class, $mockManager);

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
