<?php

namespace Tests\Unit\Models;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_handles_null_exif_data_safely(): void
    {
        $media = new Media;

        // Test Setter
        $media->exif = null;
        $this->assertNull($media->getAttributes()['exif']);

        // Test Getter
        $media->setRawAttributes(['exif' => null]);
        $this->assertNull($media->exif);
    }

    public function test_it_removes_exif_data_for_privacy(): void
    {
        $media = Media::factory()->create(['exif' => ['iso' => 100]]);

        $this->assertNull($media->exif);
    }

    public function test_it_handles_non_string_decoded_exif_safely(): void
    {
        // Simulate parent::decodeExif returning a non-string value (e.g., false or null)
        // We use a partial mock to override the parent's behavior without calling the real method
        $mockedMedia = $this->getMockBuilder(Media::class)
            ->onlyMethods(['decodeExif']) // Mock only the parent's method
            ->getMock();

        $mockedMedia->method('decodeExif')
            ->willReturn(false); // Or null, or any non-string value

        // Set a non-null raw attribute to trigger the getter logic
        $mockedMedia->setRawAttributes(['exif' => 'some_stored_value']);

        // The getter should return null when decodeExif does not return a string
        $this->assertNull($mockedMedia->exif);
    }
}
