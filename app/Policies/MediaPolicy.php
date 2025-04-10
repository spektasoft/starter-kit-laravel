<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Media $media): bool
    {
        if ($user->isNot($media->creator) && ! $this->viewAll($user)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can view all models.
     */
    public function viewAll(User $user): bool
    {
        return $user->can('view_all_media');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Media $media): bool
    {
        if ($user->isNot($media->creator) && ! $this->viewAll($user)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Media $media): bool
    {
        if ($media->isReferenced()) {
            return false;
        }
        if ($user->isNot($media->creator) && ! $this->viewAll($user)) {
            return false;
        }

        return true;
    }
}
