<?php

namespace App\Concerns;

use Illuminate\Foundation\Auth\Access\Authorizable;

trait SuperUserAuthorizable
{
    use Authorizable {
        Authorizable::can as parentCan;
        Authorizable::canAny as parentCanAny;
        Authorizable::cant as parentCant;
        Authorizable::cannot as parentCannot;
    }

    /**
     * Determine if the entity has the given abilities.
     *
     * @param  string[]|\BackedEnum|string  $abilities
     * @param  array|mixed  $arguments
     */
    public function can($abilities, $arguments = []): bool
    {
        if ($this->isSuperUser()) {
            return true;
        }

        return $this->parentCan($abilities, $arguments);
    }

    /**
     * Determine if the entity has any of the given abilities.
     *
     * @param  string[]|\BackedEnum|string  $abilities
     * @param  array|mixed  $arguments
     */
    public function canAny($abilities, $arguments = []): bool
    {
        if ($this->isSuperUser()) {
            return true;
        }

        return $this->parentCanAny($abilities, $arguments);
    }

    /**
     * Determine if the entity does not have the given abilities.
     *
     * @param  string[]|\BackedEnum|string  $abilities
     * @param  array|mixed  $arguments
     */
    public function cant($abilities, $arguments = []): bool
    {
        if ($this->isSuperUser()) {
            return false;
        }

        return $this->parentCant($abilities, $arguments);
    }

    /**
     * Determine if the entity does not have the given abilities.
     *
     * @param  string[]|\BackedEnum|string  $abilities
     * @param  array|mixed  $arguments
     */
    public function cannot($abilities, $arguments = []): bool
    {
        if ($this->isSuperUser()) {
            return false;
        }

        return $this->parentCannot($abilities, $arguments);
    }
}
