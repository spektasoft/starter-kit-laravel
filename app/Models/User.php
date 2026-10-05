<?php

namespace App\Models;

use App\Concerns\HasUserResources;
use App\Concerns\SuperUserAuthorizable;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Features as FortifyFeatures;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\Jetstream;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $id
 * @property ?string $profile_photo_media_id
 * @property string $name
 * @property string $email
 * @property ?Carbon $email_verified_at
 * @property string $password
 * @property ?string $two_factor_secret
 * @property ?string $two_factor_recovery_codes
 * @property ?Carbon $two_factor_confirmed_at
 * @property ?string $remember_token
 * @property ?string $current_team_id
 * @property ?string $profile_photo_path
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Collection<int, DatabaseNotification> $notifications
 * @property-read ?int $notifications_count
 * @property-read Collection<int, PersonalAccessToken> $tokens
 * @property-read ?int $tokens_count
 * @property-read Media|null $profilePhotoMedia
 * @property-read Collection<int, Role> $roles
 * @property-read ?int $roles_count
 * @property-read string $profile_photo_url
 * @property-read Collection<int, Export> $exports
 * @property-read Collection<int, Import> $imports
 *
 * @method static UserFactory factory($count = null, $state = [])
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User permission($permissions)
 * @method static Builder|User query()
 * @method static Builder|User role($roles, $guard = null)
 * @method array<int, string> recoveryCodes()
 */
#[Fillable([
    'name',
    'email',
    'password',
    'profile_photo_media_id',
    'email_verified_at',
])]
#[Hidden([
    'password',
    'remember_token',
    'two_factor_recovery_codes',
    'two_factor_secret',
])]
class User extends Authenticatable implements FilamentUser, HasAvatar, MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use HasRoles;
    use HasUlids;
    use HasUserResources;
    use Notifiable;
    use SuperUserAuthorizable;
    use TwoFactorAuthenticatable;

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
            'two_factor_confirmed_at' => 'datetime',
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
            return $this->profilePhotoMedia->url;
        }

        if (! boolval(config('avatar.enabled', false))) {
            return null;
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

        return in_array($this->{Fortify::username()}, $superUsers);
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

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        if (FortifyFeatures::enabled(FortifyFeatures::emailVerification())) {
            $this->notify(new VerifyEmail);
        }
    }
}
