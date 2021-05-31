<?php

namespace App\Traits;


trait Likeable
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $user
     *
     * @return bool
     */
    public function isLikedBy(Model $user): bool
    {
        if (\is_a($user, \App\User::class)) {
            if ($this->relationLoaded('likers')) {
                return $this->likers->contains($user);
            }

            return $this->likers()->where('user_id', $user->getKey())->exists();
        }

        return false;
    }

    /**
     * Return followers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function likers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\User::class,
            'likes',
            'likeable_id',
            'user_id'
        )
            ->where('likeable_type', $this->getMorphClass());
    }
}
