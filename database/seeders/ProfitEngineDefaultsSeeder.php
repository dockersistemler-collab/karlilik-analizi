<?php

namespace Database\Seeders;

use App\Models\MarketplaceFeeRule;
use App\Models\ProfitCostProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProfitEngineDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()
            ->where('role', '!=', 'super_admin')
            ->get(['id', 'tenant_id']);

        foreach ($users as $user) {
            $tenantId = (int) ($user->tenant_id ?: $user->id);

            ProfitCostProfile::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'user_id' => $user->id,
                    'is_default' => true,
                ],
                [
                    'name' => 'Default',
                    'packaging_cost' => 3,
                    'operational_cost' => 2,
                    'return_rate_default' => 5,
                    'ad_cost_default' => 0,
                ]
            );

            foreach (['trendyol', 'hepsiburada', 'amazon', 'n11'] as $marketplace) {
                MarketplaceFeeRule::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'user_id' => $user->id,
                        'marketplace' => $marketplace,
                        'sku' => null,
                        'category_id' => null,
                        'brand_id' => null,
                    ],
                    [
                        'commission_rate' => 10,
                        'fixed_fee' => 0,
                        'shipping_fee' => 0,
                        'service_fee' => 0,
                        'campaign_contribution_rate' => 0,
                        'vat_rate' => 20,
                        'priority' => 1,
                        'active' => true,
                    ]
                );
            }
        }
    }
}

