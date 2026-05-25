<?php

namespace App\Observers;

use App\Models\Media;
use App\Models\User;
use App\PathGenerators\AuthenticatedUserPathGenerator;
use Awcodes\Curator\Models\Media as CuratorMedia;
use Awcodes\Curator\Observers\MediaObserver as CuratorMediaObserver;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Throwable;

class MediaObserver extends CuratorMediaObserver
{
    /** @var string[] */
    private $supportedImageToConvertTypes = [
        'image/jpeg',
        'image/png',
    ];

    /**
     * Handle the Media "creating" event. Must adhere to the parent method's signature (CuratorMedia $media).
     */
    public function creating(CuratorMedia $media): void
    {
        if ($media instanceof Media) {
            /** @var User|null $creator */
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

        // Only apply this file moving logic if creator_id is dirty AND AuthenticatedUserPathGenerator is in use
        if ($media->isDirty('creator_id') && $pathGeneratorClass === AuthenticatedUserPathGenerator::class) {
            DB::beginTransaction();
            try {
                $newCreatorId = $media->creator_id;

                /** @var string $oldFullPath */
                $oldFullPath = $media->getOriginal('path');
                $filename = pathinfo($oldFullPath, PATHINFO_BASENAME);

                $defaultDirectoryConfig = config('curator.default_directory');

                if (! is_string($defaultDirectoryConfig) || $defaultDirectoryConfig === '') {
                    Log::error('curator.default_directory config key is missing or invalid. File move aborted.', [
                        'media_id' => $media->id,
                    ]);

                    return;
                }

                // Construct the new base directory (e.g., 'media/1')
                $newBaseDirectory = "{$defaultDirectoryConfig}/{$newCreatorId}";

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
            } catch (Throwable $e) {
                DB::rollBack();
                Log::error("Failed to move media file for media ID {$media->id} and rolled back transaction: {$e->getMessage()}");
                throw $e;
            }
        }
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
            $manager = app(ImageManager::class);
            $image = $manager->read($originalPath);
            $webpPath = pathinfo($originalPath, PATHINFO_DIRNAME)
                .'/'.pathinfo($originalPath, PATHINFO_FILENAME).'.webp';
            $image->toWebp(90)->save($webpPath);
            $oldImagePath = $media->path;
            $media->setAttribute('path', str_replace(pathinfo($media->path, PATHINFO_EXTENSION), 'webp', $media->path));
            $media->setAttribute('ext', 'webp');
            $media->setAttribute('size', filesize($webpPath));
            $media->setAttribute('type', 'image/webp');
            $updated = $media->save();
            if ($updated) {
                Storage::disk($media->disk)->delete($oldImagePath);
            }
        } catch (Exception $e) {
            Log::error('Error converting image to WebP: '.$e->getMessage(), ['media_id' => $media->id, 'path' => $media->path]);
        }
    }

    private function removeExif(Media $media): void
    {
        $media->exif = null;
        $media->saveQuietly();
    }
}
