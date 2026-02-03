<?php

namespace App\Actions\Jetstream;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     */
    public function delete(User $user): void
    {
        if ($user->isReferenced()) {
            throw ValidationException::withMessages([
                'delete_account' => [__('user.account_cannot_be_deleted')],
            ]);
        }

        $user->deleteProfilePhoto();
        $user->deleteProfilePhotoMedia();

        $user->tokens->each->delete();
        $user->delete();
    }
}
