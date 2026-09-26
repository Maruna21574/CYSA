<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Runtime settings of the whole platform (e.g. the system-wide banner). Cached, because the
 * layout reads them on every request.
 */
#[Fillable(['key', 'value'])]
class SystemSetting extends Model
{
    private const CACHE_KEY = 'system-settings';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever(self::CACHE_KEY, fn (): array => Schema::hasTable('system_settings')
            ? static::query()->pluck('value', 'key')->all()
            : []);

        return $all[$key] ?? $default;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
