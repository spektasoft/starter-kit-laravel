<?php

namespace Tests\Unit\Models;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_handles_null_exif_data_safely()
    {
        $media = new Media;

        // Test Setter
        $media->exif = null;
        $this->assertNull($media->getAttributes()['exif']);

        // Test Getter
        $media->setRawAttributes(['exif' => null]);
        $this->assertNull($media->exif);
    }

    public function test_it_removes_exif_data_for_privacy()
    {
        $media = Media::factory()->create(['exif' => ['iso' => 100]]);

        $this->assertNull($media->exif);
    }
}
