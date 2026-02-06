<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CorrelationId
{
    public static function current(): ?string
    {
        return app()->bound('correlation_id') ? app('correlation_id') : null;
    }

    public static function set(?string $value = null): string
    {
        $id = $value ?: (string) Str::uuid();
        app()->instance('correlation_id', $id);
        Log::withContext(['correlation_id' => $id]);

        return $id;
    }
}
