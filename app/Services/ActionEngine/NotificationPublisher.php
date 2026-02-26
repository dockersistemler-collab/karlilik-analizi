<?php

namespace App\Services\ActionEngine;

use App\Enums\NotificationType;
use App\Models\ActionRecommendation;
use App\Services\Notifications\NotificationService;

class NotificationPublisher
{
    public function __construct(
        private readonly NotificationService $notifications
    ) {
    }

    public function publishNewRecommendation(ActionRecommendation $recommendation): void
    {
        if ($recommendation->status !== 'open') {
            return;
        }

        $type = in_array($recommendation->severity, ['high', 'critical'], true)
            ? NotificationType::Operational->value
            : NotificationType::Info->value;

        $this->notifications->createNotification([
            'tenant_id' => (int) $recommendation->tenant_id,
            'user_id' => (int) $recommendation->user_id,
            'audience_role' => 'admin',
            'marketplace' => $recommendation->marketplace,
            'source' => 'action_engine',
            'type' => $type,
            'channel' => 'in_app',
            'title' => 'Yeni aksiyon onerisi',
            'body' => $recommendation->title,
            'data' => [
                'recommendation_id' => $recommendation->id,
                'action_type' => $recommendation->action_type,
                'severity' => $recommendation->severity,
            ],
            'action_url' => route('portal.action-engine.show', $recommendation->id),
            'dedupe_key' => 'action_engine:new:'.$recommendation->id,
            'dedupe_window_minutes' => 1440,
        ]);
    }
}

