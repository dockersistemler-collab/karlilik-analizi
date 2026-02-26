<?php

namespace App\Services\MarketplaceRisk;

use App\Enums\NotificationType;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Carbon\CarbonImmutable;

class NotificationPublisher
{
    public function __construct(
        private readonly NotificationService $notifications
    ) {
    }

    public function publishIfNeeded(
        User $user,
        string $marketplace,
        CarbonImmutable $date,
        float $riskScore,
        string $status,
        array $reasons
    ): void {
        if (!in_array($status, ['warning', 'critical'], true)) {
            return;
        }

        $type = $status === 'critical' ? NotificationType::Critical->value : NotificationType::Operational->value;
        $title = $status === 'critical'
            ? "Kritik risk: {$marketplace}"
            : "Risk uyarisi: {$marketplace}";

        $drivers = collect((array) ($reasons['drivers'] ?? []))
            ->pluck('metric')
            ->filter()
            ->take(3)
            ->implode(', ');

        $body = sprintf(
            '%s icin %s tarihli risk skoru %.2f. Etkileyen metrikler: %s',
            strtoupper($marketplace),
            $date->toDateString(),
            $riskScore,
            $drivers !== '' ? $drivers : '-'
        );

        $this->notifications->createNotification([
            'tenant_id' => (int) ($user->tenant_id ?: $user->id),
            'user_id' => $user->id,
            'audience_role' => 'admin',
            'marketplace' => strtolower($marketplace),
            'source' => 'marketplace_risk',
            'type' => $type,
            'channel' => 'in_app',
            'title' => $title,
            'body' => $body,
            'data' => [
                'risk_score' => $riskScore,
                'status' => $status,
                'date' => $date->toDateString(),
                'reasons' => $reasons,
            ],
            'action_url' => route('portal.marketplace-risk.index'),
            'dedupe_key' => sprintf(
                'marketplace_risk:%d:%s:%s:%s',
                (int) ($user->tenant_id ?: $user->id),
                strtolower($marketplace),
                $status,
                $date->toDateString()
            ),
            'dedupe_window_minutes' => 1440,
        ]);
    }
}

