<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['school_id', 'name', 'slug'])]
class Tag extends Model
{
    /**
     * @return BelongsToMany<Question, $this>
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class);
    }

    /**
     * Turns "heslá, 2FA , heslá" into tag IDs of the school, creating missing tags.
     *
     * @return list<int>
     */
    public static function idsFromInput(string $input, int $schoolId): array
    {
        return collect(explode(',', $input))
            ->map(fn (string $name): string => Str::limit(trim(preg_replace('/\s+/', ' ', $name)), 50, ''))
            ->filter(fn (string $name): bool => $name !== '' && Str::slug($name) !== '')
            ->unique(fn (string $name): string => Str::slug($name))
            ->take(20)
            ->map(fn (string $name): int => static::firstOrCreate(
                ['school_id' => $schoolId, 'slug' => Str::slug($name)],
                ['name' => $name],
            )->id)
            ->values()
            ->all();
    }
}
