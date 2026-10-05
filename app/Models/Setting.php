<?php

namespace App\Models;

use App\Support\Features;
use App\Support\SystemSettings;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use LogsActivity;

    protected $table = 'business_settings';

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get setting value by key, with optional default.
     */
    public static function get(string $key, $default = null)
    {
        if (! Features::wholesaleEnabled() && (str_contains($key, 'wholesale') || in_array($key, ['default_pos_type', 'login_landing_page'], true))) {
            return SystemSettings::get($key, $default, 'business_settings');
        }
        $setting = self::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value by key.
     */
    public static function set(string $key, $value)
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
