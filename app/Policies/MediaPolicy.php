<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Media $media): bool
    {
        if ($user->id === $media->creator?->id) {
            return true;
        }

        return $user->can('view_media');
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
        if ($user->id === $media->creator?->id) {
            return true;
        }

        return $user->can('update_media');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Media $media): bool
    {
        if ($media->isReferenced()) {
            return false;
        }
        if ($user->id === $media->creator?->id) {
            return true;
        }

        return $user->can('delete_media');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Media $media): bool
    {
        if ($media->isReferenced()) {
            return false;
        }
        if ($user->id === $media->creator?->id) {
            return true;
        }

        return $user->can('force_delete_media');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Media $media): bool
    {
        if ($user->id === $media->creator?->id) {
            return true;
        }

        return $user->can('restore_media');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Media $media): bool
    {
        if ($user->id === $media->creator?->id) {
            return true;
        }

        return $user->can('replicate_media');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return true;
    }
}
