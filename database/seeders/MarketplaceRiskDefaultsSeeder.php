<?php

namespace Database\Seeders;

use App\Models\MarketplaceRiskProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class MarketplaceRiskDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()
            ->where('role', '!=', 'super_admin')
            ->get(['id', 'tenant_id']);

        $marketplaces = ['trendyol', 'hepsiburada', 'amazon', 'n11'];

        foreach ($users as $user) {
            $tenantId = (int) ($user->tenant_id ?: $user->id);
            foreach ($marketplaces as $marketplace) {
                $existsDefault = MarketplaceRiskProfile::query()
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $user->id)
                    ->where('marketplace', $marketplace)
                    ->where('is_default', true)
                    ->exists();

                if ($existsDefault) {
                    continue;
                }

                MarketplaceRiskProfile::query()->create([
                    'tenant_id' => $tenantId,
                    'user_id' => $user->id,
                    'marketplace' => $marketplace,
                    'name' => 'Default',
                    'weights' => [
                        'late_shipment_rate' => 0.16,
                        'cancellation_rate' => 0.16,
                        'return_rate' => 0.14,
                        'performance_score' => 0.18,
                        'rating_score' => 0.12,
                        'odr' => 0.14,
                        'valid_tracking_rate' => 0.10,
                    ],
                    'thresholds' => ['warning' => 45, 'critical' => 70],
                    'metric_thresholds' => [
                        'late_shipment_rate' => ['warning' => 4, 'critical' => 8, 'direction' => 'higher_worse'],
                        'cancellation_rate' => ['warning' => 2, 'critical' => 5, 'direction' => 'higher_worse'],
                        'return_rate' => ['warning' => 8, 'critical' => 15, 'direction' => 'higher_worse'],
                        'performance_score' => ['warning' => 85, 'critical' => 70, 'direction' => 'lower_worse'],
                        'rating_score' => ['warning' => 4.4, 'critical' => 4.0, 'direction' => 'lower_worse'],
                        'odr' => ['warning' => 1, 'critical' => 2, 'direction' => 'higher_worse'],
                        'valid_tracking_rate' => ['warning' => 97, 'critical' => 93, 'direction' => 'lower_worse'],
                    ],
                    'is_default' => true,
                ]);
            }
        }
    }
}

