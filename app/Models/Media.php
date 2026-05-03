<?php

namespace App\Models;

use App\Observers\MediaObserver;
use Awcodes\Curator\Models\Media as CuratorMedia;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * @property string $id
 * @property string|null $url
 * @property string|null $thumbnail_url
 * @property string|null $medium_url
 * @property string|null $large_url
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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static MediaFactory factory(...$parameters)
 */
#[ObservedBy([MediaObserver::class])]
class Media extends CuratorMedia
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    use HasUlids;

    protected $table = 'media';

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
     * @return Attribute<array<string, string>|null, array<string, string>|null>
     */
    protected function exif(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ! is_string($value) ? null : json_decode($value, true),
            set: fn ($value) => is_null($value) ? null : json_encode($value),
        );
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
