<?php

namespace App\Models;

use App\Observers\MediaObserver;
use Awcodes\Curator\Models\Media as CuratorMedia;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * @property string $id
 * @property string $disk
 * @property string $path
 * @property string $creator_id
 * @property User $creator
 * @property string $directory
 * @property string $visibility
 * @property string $name
 * @property int|null $width
 * @property int|null $height
 * @property int|null $size
 * @property string $type
 * @property string $ext
 * @property string|null $alt
 * @property string|null $title
 * @property string|null $description
 * @property string|null $caption
 * @property array<string, string>|null $exif
 * @property array<string, mixed>|null $curations
 * @property string $size_for_humans
 * @property string $pretty_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Database\Factories\MediaFactory factory(...$parameters)
 */
#[ObservedBy([MediaObserver::class])]
class Media extends CuratorMedia
{
    /** @use HasFactory<\Database\Factories\MediaFactory> */
    use HasFactory;

    use HasUlids;

    protected static function booted()
    {
        static::addGlobalScope('curator-panel', function (Builder $builder) {
            if (Gate::check('viewAll', Media::class)) {
                return;
            }

            /** @var Collection<string, mixed> */
            $traces = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10));
            /** @var Collection<int, string> */
            $files = $traces->pluck('file');

            if ($files->contains(fn ($item) => strpos($item, 'CuratorPanel') !== false)) {
                $builder->whereCreatorId(User::auth()?->id);
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * @return User
     */
    public function getCreatorAttribute()
    {
        if (! $this->relationLoaded('creator')) {
            $this->load('creator');
        }

        /** @var User */
        $creator = $this->getRelation('creator');

        return $creator;
    }

    public function isReferenced(): bool
    {
        if ($this->usersWithThisAsProfilePhoto()->exists()) {
            return true;
        }

        return false;
    }

    /**
     * @return HasMany<User, $this>
     */
    public function usersWithThisAsProfilePhoto(): HasMany
    {
        return $this->hasMany(User::class, 'profile_photo_media_id');
    }
}
