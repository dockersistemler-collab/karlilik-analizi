<?php

use App\Models\Module;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

if (!function_exists('module_enabled')) {
    function module_enabled(string $code): bool
    {
        $normalized = trim($code);
        if ($normalized === '') {
            return false;
        }

        if (!Schema::hasTable('modules')) {
            return false;
        }

        return Cache::remember("module_enabled:{$normalized}", now()->addMinutes(10), function () use ($normalized): bool {
            return Module::query()
                ->where('code', $normalized)
                ->where('is_active', true)
                ->exists();
        });
    }
}

