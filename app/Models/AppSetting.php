<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, $default = null)
    {
        $value = static::query()->where('key', $key)->value('value');

        if ($value === null) {
            return $default;
        }

        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        return $value;
    }

    public static function setValue(string $key, $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value]
        );
    }
}
