<?php

namespace App\Services\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Models\NotificationPreference;
use App\Models\User;

class PreferenceResolver
{
    public function isEnabled(User $user, NotificationType $type, NotificationChannel $channel, ?string $marketplace): bool
    {
        $preference = $this->resolvePreference($user, $type, $channel, $marketplace);
        if ($preference) {
            return (bool) $preference->enabled;
        }

        return $this->defaultEnabled($type, $channel);
    }

    public function resolveQuietHours(User $user, NotificationType $type, ?string $marketplace): ?array
    {
        $preference = $this->resolvePreference($user, $type, NotificationChannel::Email, $marketplace);
        if ($preference && is_array($preference->quiet_hours) && !empty($preference->quiet_hours)) {
            return $preference->quiet_hours;
        }

        return null;
    }

    public function resolvePreference(User $user, NotificationType $type, NotificationChannel $channel, ?string $marketplace): ?NotificationPreference
    {
        $base = NotificationPreference::query()
            ->where('tenant_id', $user->id)
            ->where('user_id', $user->id)
            ->where('type', $type->value)
            ->where('channel', $channel->value);

        if ($marketplace) {
            $specific = (clone $base)->where('marketplace', $marketplace)->first();
            if ($specific) {
                return $specific;
            }
        }

        return $base->whereNull('marketplace')->first();
    }

    private function defaultEnabled(NotificationType $type, NotificationChannel $channel): bool
    {
        return match ($type) {
            NotificationType::Critical => true,
            NotificationType::Operational => $channel === NotificationChannel::InApp,
            NotificationType::Info => false,
        };
    }
}