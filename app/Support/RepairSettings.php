<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class RepairSettings
{
    /** @return array<string, array{0:string, 1:string, 2:string|int}> */
    public static function definitions(): array
    {
        return config('repairs.settings', []);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return DB::table('repair_settings')->where('key', $key)->value('value') ?? $default ?? config('repairs.settings.'.$key.'.2');
    }

    public static function bool(string $key): bool
    {
        return filter_var(self::get($key), FILTER_VALIDATE_BOOLEAN);
    }
}
