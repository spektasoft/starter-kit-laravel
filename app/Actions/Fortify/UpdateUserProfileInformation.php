<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
        ])->validateWithBag('updateProfileInformation');

        if (isset($input['photo'])) {
            /** @var \Illuminate\Http\UploadedFile */
            $photo = $input['photo'];
            $user->updateProfilePhoto($photo);
        }

        $sessionKey = 'avatar-'.$user->id;
        if (Session::has($sessionKey)) {
            Session::remove($sessionKey);
        }

        if (Jetstream::managesProfilePhotos()) {
            $user->profilePhotoMedia()->associate($input['profile_photo_media_id'] ?? null);
            $user->save();
        }

        if ($input['email'] !== $user->email) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
            ])->save();
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        if (Features::enabled(Features::emailVerification())) {
            $user->sendEmailVerificationNotification();
        }
    }
}
