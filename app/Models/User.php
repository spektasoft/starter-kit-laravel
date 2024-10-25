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
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\Jetstream;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements HasAvatar, MustVerifyEmail
{
    use HasApiTokens;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasProfilePhoto;

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

    public function getFilamentAvatarUrl(): ?string
    {
        if (Jetstream::managesProfilePhotos()) {
            return $this->profilePhotoMedia?->getSignedUrl();
        }

        return null;
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
