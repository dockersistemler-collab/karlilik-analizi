<?php

namespace App\Services\ActionEngine;

use App\Models\MarketplaceCampaign;
use App\Models\MarketplaceExternalShock;
use App\Models\MarketplacePriceHistory;
use Carbon\CarbonImmutable;

class CampaignCalendarApplier
{
    public function applyForTenant(int $tenantId, int $userId, ?int $campaignDbId = null): array
    {
        $campaigns = MarketplaceCampaign::query()
            ->where('tenant_id', $tenantId)
            ->when($campaignDbId !== null, fn ($q) => $q->where('id', $campaignDbId))
            ->with('items')
            ->get();

        $updated = 0;
        foreach ($campaigns as $campaign) {
            foreach ($campaign->items as $item) {
                $from = CarbonImmutable::parse($campaign->start_date);
                $to = CarbonImmutable::parse($campaign->end_date);

                for ($day = $from; $day->lessThanOrEqualTo($to); $day = $day->addDay()) {
                    $affected = MarketplacePriceHistory::query()
                        ->where('tenant_id', $tenantId)
                        ->where('marketplace', strtolower((string) $campaign->marketplace))
                        ->where('sku', (string) $item->sku)
                        ->whereDate('date', $day->toDateString())
                        ->update([
                            'is_promo_day' => true,
                            'promo_source' => 'import',
                            'promo_campaign_id' => (string) $campaign->campaign_id,
                        ]);

                    if ($affected > 0) {
                        $updated += $affected;
                    }

                    MarketplaceExternalShock::query()->updateOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'marketplace' => strtolower((string) $campaign->marketplace),
                            'sku' => (string) $item->sku,
                            'date' => $day->toDateString(),
                            'shock_type' => 'CAMPAIGN',
                            'detected_by' => 'import',
                        ],
                        [
                            'user_id' => $userId,
                            'severity' => 'medium',
                            'details' => [
                                'campaign_id' => $campaign->campaign_id,
                                'discount_rate' => (float) $item->discount_rate,
                            ],
                        ]
                    );
                }
            }
        }

        return [
            'campaigns' => $campaigns->count(),
            'updated_rows' => $updated,
        ];
    }
}

