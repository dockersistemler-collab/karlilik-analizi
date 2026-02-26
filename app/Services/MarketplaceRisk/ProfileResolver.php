<?php

namespace App\Services\MarketplaceRisk;

use App\Models\MarketplaceRiskProfile;

class ProfileResolver
{
    public function resolveDefault(int $tenantId, int $userId, string $marketplace): MarketplaceRiskProfile
    {
        $marketplace = strtolower($marketplace);

        $profile = MarketplaceRiskProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('marketplace', $marketplace)
            ->where('is_default', true)
            ->latest('id')
            ->first();

        if ($profile) {
            return $profile;
        }

        return MarketplaceRiskProfile::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'marketplace' => $marketplace,
            'name' => 'Default',
            'is_default' => true,
            'weights' => [
                'late_shipment_rate' => 0.16,
                'cancellation_rate' => 0.16,
                'return_rate' => 0.14,
                'performance_score' => 0.18,
                'rating_score' => 0.12,
                'odr' => 0.14,
                'valid_tracking_rate' => 0.10,
            ],
            'thresholds' => [
                'warning' => 45,
                'critical' => 70,
            ],
            'metric_thresholds' => [
                'late_shipment_rate' => ['warning' => 4, 'critical' => 8, 'direction' => 'higher_worse'],
                'cancellation_rate' => ['warning' => 2, 'critical' => 5, 'direction' => 'higher_worse'],
                'return_rate' => ['warning' => 8, 'critical' => 15, 'direction' => 'higher_worse'],
                'performance_score' => ['warning' => 85, 'critical' => 70, 'direction' => 'lower_worse'],
                'rating_score' => ['warning' => 4.4, 'critical' => 4.0, 'direction' => 'lower_worse'],
                'odr' => ['warning' => 1, 'critical' => 2, 'direction' => 'higher_worse'],
                'valid_tracking_rate' => ['warning' => 97, 'critical' => 93, 'direction' => 'lower_worse'],
            ],
        ]);
    }
}

