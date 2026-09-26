<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Cached gamification totals of a user (XP, level, streak).
 */
#[Fillable(['user_id'])]
class UserStat extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $attributes = [
        'xp_total' => 0,
        'level' => 1,
        'current_streak' => 0,
        'longest_streak' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'xp_total' => 'integer',
            'level' => 'integer',
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
            'last_activity_date' => 'date',
        ];
    }
}
