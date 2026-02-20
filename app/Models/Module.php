<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'billing_type',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        $forget = static function (self $module): void {
            $codes = array_filter([
                (string) $module->code,
                (string) $module->getOriginal('code'),
            ]);

            foreach (array_unique($codes) as $code) {
                Cache::forget('module_enabled:' . trim($code));
            }
        };

        static::saved($forget);
        static::deleted($forget);
    }

    public function userModules()
    {
        return $this->hasMany(UserModule::class);
    }
}

