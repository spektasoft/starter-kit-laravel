<?php

namespace App\Models;

use App\Concerns\SuperUserAuthorizable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\Jetstream;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar, MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use HasRoles;
    use HasUlids;
    use Notifiable;
    use SuperUserAuthorizable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'profile_photo_media_id',
        'name',
        'email',
        'password',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    public static function auth(): ?User
    {
        $user = Auth::user();
        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Delete the user's profile photo.
     *
     * @return void
     */
    public function deleteProfilePhotoMedia()
    {
        if (! Features::managesProfilePhotos()) {
            return;
        }

        if (is_null($this->profile_photo_media_id)) {
            return;
        }

        $this->forceFill([
            'profile_photo_media_id' => null,
        ])->save();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (Jetstream::managesProfilePhotos() && $this->profilePhotoMedia !== null) {
            return $this->profilePhotoMedia->getSignedUrl();
        }

        if (! boolval(config('avatar.enabled', false))) {
            return null;
        }

        return null;
    }

    public function isReferenced(): bool
    {
        if ($this->media()->exists()) {
            return true;
        }
        if ($this->pages()->exists()) {
            return true;
        }

        return false;
    }

    public function isSuperUser(): bool
    {
        /** @var string[] */
        $superUsers = config('auth.super_users', []);

        if (blank($superUsers)) {
            return false;
        }

        return in_array($this->{Fortify::username()}, $superUsers);
    }

    /**
     * Get all of the media for the User
     *
     * @return HasMany<Media, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'creator_id');
    }

    /**
     * @return HasMany<Page, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class, 'creator_id');
    }

    /**
     * Get the profilePhotoMedia that owns the User
     *
     * @return BelongsTo<Media, $this>
     */
    public function profilePhotoMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'profile_photo_media_id');
    }
}
