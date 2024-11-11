<?php

namespace App\Models;

use Filament\Models\Contracts\HasAvatar;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\Jetstream;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasAvatar, MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use HasRoles;
    use HasUlids;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'profile_photo_media_id',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

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

    public static function auth(): ?User
    {
        $user = Auth::user();
        if ($user instanceof User) {
            return $user;
        }

        return null;
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
        if (Jetstream::managesProfilePhotos()) {
            return $this->profilePhotoMedia?->getSignedUrl();
        }

        return null;
    }

    public function isSuperUser(): bool
    {
        /** @var string[] */
        $superUsers = config('auth.super_users', []);

        if (blank($superUsers)) {
            return false;
        }

        return in_array($this->email, $superUsers);
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
     * Get the profilePhotoMedia that owns the User
     *
     * @return BelongsTo<Media, $this>
     */
    public function profilePhotoMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'profile_photo_media_id');
    }
}
