<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_id', 'name', 'slug'])]
class Category extends Model
{
    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Global categories plus those of the user's school.
     *
     * @param  Builder<Category>  $query
     */
    public function scopeAvailableFor(Builder $query, User $user): void
    {
        $query->where(fn (Builder $query) => $query->whereNull('school_id')->orWhere('school_id', $user->school_id));
    }

    /**
     * @return array<int, string>
     */
    public static function optionsFor(User $user): array
    {
        return static::availableFor($user)->orderBy('name')->pluck('name', 'id')->all();
    }
}
