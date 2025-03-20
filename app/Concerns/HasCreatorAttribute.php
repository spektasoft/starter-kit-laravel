<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasCreatorAttribute
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * @return User
     */
    public function getCreatorAttribute()
    {
        if (! $this->relationLoaded('creator')) {
            $this->load('creator');
        }

        /** @var User */
        $creator = $this->getRelation('creator');

        return $creator;
    }
}
