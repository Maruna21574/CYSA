<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Keeps a contiguous 0..n "position" column when an item is dragged to a new place.
 */
class Positioning
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $siblings  query for the list the item ends up in (without ordering)
     * @param  TModel  $item
     */
    public static function moveTo(Builder $siblings, Model $item, int $position): void
    {
        DB::transaction(function () use ($siblings, $item, $position): void {
            $ids = (clone $siblings)
                ->whereKeyNot($item->getKey())
                ->orderBy('position')
                ->orderBy($item->getKeyName())
                ->pluck($item->getKeyName())
                ->all();

            array_splice($ids, max(0, min($position, count($ids))), 0, [$item->getKey()]);

            foreach ($ids as $index => $id) {
                $item->newQuery()->whereKey($id)->update(['position' => $index]);
            }
        });
    }

    /**
     * @param  Builder<Model>|QueryBuilder  $siblings
     */
    public static function next(Builder|QueryBuilder $siblings): int
    {
        return ((int) $siblings->max('position')) + 1;
    }
}
