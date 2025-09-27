<?php

namespace App\Observers;

use App\Models\Media;
use Awcodes\Curator\Models\Media as CuratorMedia;
use Awcodes\Curator\Observers\MediaObserver as CuratorMediaObserver;
use Awcodes\Curator\PathGenerators\UserPathGenerator;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
     * Handle the Media "creating" event. Must adhere to the parent method's signature (CuratorMedia $media).
     */
    public function creating(CuratorMedia $media): void
    {
        if ($media instanceof Media) {
            /** @var \App\Models\User|null $creator */
            $creator = $media->creator;
            if (is_null($creator)) {
                if (Auth::check()) {
                    $media->creator()->associate(Auth::user());
                } else {
                    throw new AuthenticationException(
                        'Cannot create Media without an authenticated user to assign as the creator.'
                    );
                }
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

    /**
     * Handle the Media "updated" event.
     *
     * @param  Media  $media
     */
    public function updating(CuratorMedia $media): void
    {
        // Get the configured path generator class
        $pathGeneratorClass = config('curator.path_generator');

        // Only apply this file moving logic if creator_id is dirty AND UserPathGenerator is in use
        if ($media->isDirty('creator_id') && $pathGeneratorClass === UserPathGenerator::class) {
            DB::beginTransaction();
            try {
                $newCreatorId = $media->creator_id;

                /** @var string $oldFullPath */
                $oldFullPath = $media->getOriginal('path');
                $filename = pathinfo($oldFullPath, PATHINFO_BASENAME);

                // Get the base directory from Curator's config, defaulting to 'media'
                $baseDirectoryConfig = config('curator.directory', 'media');

                // Ensure $baseDirectoryConfig is a string
                if (! is_string($baseDirectoryConfig)) {
                    Log::warning('Curator directory config is not a string. Defaulting to "media".', ['config_value' => $baseDirectoryConfig]);
                    $baseDirectoryConfig = 'media';
                }

                // Construct the new base directory (e.g., 'media/1')
                $newBaseDirectory = "{$baseDirectoryConfig}/{$newCreatorId}";

                // Construct the new full path (e.g., 'media/1/filename.ext')
                $newFullPath = "{$newBaseDirectory}/{$filename}";

                // Ensure the new directory exists
                Storage::disk($media->disk)->makeDirectory($newBaseDirectory);

                // Move the file only if the old path exists and the old and new paths are different
                if ($oldFullPath && Storage::disk($media->disk)->exists($oldFullPath) && $oldFullPath !== $newFullPath) {
                    Storage::disk($media->disk)->move($oldFullPath, $newFullPath);

                    // Update the media model's path and directory attributes
                    $media->path = $newFullPath;
                    $media->directory = $newBaseDirectory;

                    // Save the model quietly to prevent re-triggering the observer
                    $media->saveQuietly();
                } elseif ($oldFullPath && ! Storage::disk($media->disk)->exists($oldFullPath)) {
                    Log::warning("MediaObserver: Source file not found at old path: {$oldFullPath} for media ID: {$media->id}");
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error("Failed to move media file for media ID {$media->id} and rolled back transaction: {$e->getMessage()}");
                throw $e;
            }
        }
        // If the path generator is not UserPathGenerator, or creator_id is not dirty,
        // this custom file moving logic is skipped.
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

        try {
            $originalPath = Storage::disk($media->disk)->path($media->path);
            $image = Image::make($originalPath);
            $webpPath = pathinfo($originalPath, PATHINFO_DIRNAME).'/'.pathinfo($originalPath, PATHINFO_FILENAME).'.webp';
            $image->encode('webp', 90)->save($webpPath);
            $oldImagePath = $media->path;
            $media->setAttribute('path', str_replace(pathinfo($media->path, PATHINFO_EXTENSION), 'webp', $media->path));
            $media->setAttribute('ext', 'webp');
            $media->setAttribute('size', filesize($webpPath));
            $media->setAttribute('type', 'image/webp');
            $updated = $media->save();
            if ($updated) {
                Storage::disk($media->disk)->delete($oldImagePath);
            }
        } catch (\Exception $e) {
            Log::error('Error converting image to WebP: '.$e->getMessage(), ['media_id' => $media->id, 'path' => $media->path]);
        }
    }

    private function removeExif(Media $media): void
    {
        $media->setAttribute('exif', null);
    }
}
