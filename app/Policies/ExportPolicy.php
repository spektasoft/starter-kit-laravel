<?php

namespace App\Policies;

use App\Models\Export;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Export $export): bool
    {
        if ($user->is($export->user) || $user->is($export->creator) || $this->viewAll($user)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view all models.
     */
    public function viewAll(User $user): bool
    {
        return $user->can('view_all_export');
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
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
    public function update(User $user, Export $export): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Export $export): bool
    {
        if ($user->is($export->user) || $user->is($export->creator) || $this->viewAll($user)) {
            return true;
        }

        return false;
    }
}
