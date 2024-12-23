<?php

namespace App\Models;

use App\Observers\MediaObserver;
use Awcodes\Curator\Models\Media as CuratorMedia;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $disk
 * @property string $path
 */
#[ObservedBy([MediaObserver::class])]
class Media extends CuratorMedia
{
    use HasUlids;

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReferenced(): bool
    {
        if ($this->usersWithThisAsProfilePhoto()->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Get all of the Users for the Media
     *
     * @return HasMany<User, $this>
     */
    public function usersWithThisAsProfilePhoto(): HasMany
    {
        return $this->hasMany(User::class, 'profile_photo_media_id');
    }
}
