<?php

namespace App\Observers;

use App\Models\Media;
use Awcodes\Curator\Models\Media as CuratorMedia;
use Awcodes\Curator\Observers\MediaObserver as CuratorMediaObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class MediaObserver extends CuratorMediaObserver
{
    /** @var string[] */
    private $supportedImageToConvertTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Handle the Media "creating" event.
     */
    public function creating(CuratorMedia $media): void
    {
        if ($media instanceof Media) {
            if ($media->creator === null) {
                $media->creator()->associate(Auth::user());
            }
        }

        parent::creating($media);
    }

    /**
     * Handle the Media "created" event.
     */
    public function created(Media $media): void
    {
        $this->removeExif($media);
        $this->convertToWebP($media);
    }

    private function convertToWebP(Media $media): void
    {
        if (strpos($media->type, 'image') !== 0) {
            return;
        }

        $type = strtolower($media->type);
        if (collect($this->supportedImageToConvertTypes)->doesntContain($type)) {
            return;
        }

        $originalPath = Storage::disk($media->disk)->path($media->path);
        $image = Image::make($originalPath);
        $webpPath = pathinfo($originalPath, PATHINFO_DIRNAME).'/'.pathinfo($originalPath, PATHINFO_FILENAME).'.webp';
        $image->encode('webp', 90)->save($webpPath);
        $oldImagePath = $media->path;
        $media->update([
            'path' => str_replace(pathinfo($media->path, PATHINFO_EXTENSION), 'webp', $media->path),
            'ext' => 'webp',
            'size' => filesize($webpPath),
            'type' => 'image/webp',
        ]);
        Storage::disk($media->disk)->delete($oldImagePath);
    }

    private function removeExif(Media $media): void
    {
        $media->update([
            'exif' => null,
        ]);
    }
}
