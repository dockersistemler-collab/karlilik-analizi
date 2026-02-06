<?php

namespace App\Services\SystemSettings;

use App\Models\SystemSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class SettingsRepository
{
    private const CACHE_TTL_SECONDS = 300;

    public function get(string $group, string $key, $default = null)
    {
        $settings = $this->getGroup($group);

        if (!array_key_exists($key, $settings)) {
            return $default;
        }
$entry = $settings[$key];
        $value = $entry['value'];

        if (!($entry['is_encrypted'] ?? false)) {
            return $value ?? $default;
        }

        if ($value === null || $value === '') {
            return $default;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $exception) {
            Log::warning('system_settings.decrypt_failed', [
                'group' => $group,
                'key' => $key,
                'error' => $exception->getMessage(),
            ]);
        }

        return $default;
    }

    public function set(string $group, string $key, $value, bool $encrypted = false, ?int $actorUserId = null): void
    {
        $payload = $this->normalizeValue($value);

        if ($encrypted && $payload !== null) {
            $payload = Crypt::encryptString($payload);
        }

        $attempts = app()->environment('testing') ? 3 : 1;
        $backoffMs = 50;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                SystemSetting::query()->updateOrCreate(
                    ['group' => $group, 'key' => $key],
                    [
                        'value' => $payload,
                        'is_encrypted' => $encrypted,
                        'updated_by_user_id' => $actorUserId,
                    ]
                );
                break;
            } catch (QueryException $exception) {
                if ($attempt === $attempts || !$this->shouldRetryDeadlock($exception)) {
                    throw $exception;
                }

                usleep($backoffMs * 1000);
                $backoffMs *= 2;
            }
        }

        Cache::forget($this->cacheKey($group));
    }

    private function getGroup(string $group): array
    {
        return Cache::remember($this->cacheKey($group), self::CACHE_TTL_SECONDS, function () use ($group) {
            return SystemSetting::query()
                ->where('group', $group)
                ->get()
                ->mapWithKeys(function (SystemSetting $setting) {
                    return [
                        $setting->key => [
                            'value' => $setting->value,
                            'is_encrypted' => $setting->is_encrypted,
                        ],
                    ];
                })
                ->all();
        });
    }

    private function cacheKey(string $group): string
    {
        return 'system_settings:'.$group;
    }

    private function normalizeValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    private function shouldRetryDeadlock(QueryException $exception): bool
    {
        $errorInfo = $exception->errorInfo ?? [];

        if (is_array($errorInfo) && ($errorInfo[1] ?? null) === 1213) {
            return true;
        }

        if ((string) $exception->getCode() === '40001') {
            return true;
        }

        return str_contains($exception->getMessage(), 'Deadlock found');
    }
}
