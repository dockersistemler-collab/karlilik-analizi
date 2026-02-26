<?php

namespace App\Services\ActionEngine;

use App\Models\MarketplaceCampaign;
use App\Models\MarketplaceCampaignItem;
use RuntimeException;

class CampaignCsvImporter
{
    public function import(int $tenantId, int $userId, string $path): array
    {
        $handle = fopen($path, 'rb');
        if (!$handle) {
            throw new RuntimeException('CSV dosyasi acilamadi.');
        }

        $header = fgetcsv($handle);
        if (!is_array($header)) {
            fclose($handle);
            throw new RuntimeException('CSV baslik satiri okunamadi.');
        }

        $map = [];
        foreach ($header as $i => $name) {
            $map[strtolower(trim((string) $name))] = $i;
        }

        foreach (['marketplace', 'campaign_id', 'start_date', 'end_date', 'sku', 'discount_rate'] as $required) {
            if (!array_key_exists($required, $map)) {
                fclose($handle);
                throw new RuntimeException("CSV kolon eksik: {$required}");
            }
        }

        $campaigns = 0;
        $items = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $marketplace = strtolower(trim((string) ($row[$map['marketplace']] ?? '')));
            $campaignId = trim((string) ($row[$map['campaign_id']] ?? ''));
            $name = array_key_exists('campaign_name', $map) ? trim((string) ($row[$map['campaign_name']] ?? '')) : null;
            $startDate = trim((string) ($row[$map['start_date']] ?? ''));
            $endDate = trim((string) ($row[$map['end_date']] ?? ''));
            $sku = trim((string) ($row[$map['sku']] ?? ''));
            $discountRate = (float) str_replace(',', '.', trim((string) ($row[$map['discount_rate']] ?? '0')));

            if ($marketplace === '' || $campaignId === '' || $startDate === '' || $endDate === '' || $sku === '') {
                continue;
            }

            $campaign = MarketplaceCampaign::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'marketplace' => $marketplace,
                    'campaign_id' => $campaignId,
                ],
                [
                    'user_id' => $userId,
                    'name' => $name ?: $campaignId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'source' => 'import',
                    'meta' => ['imported' => true],
                ]
            );
            $campaigns++;

            MarketplaceCampaignItem::query()->updateOrCreate(
                ['campaign_id' => $campaign->id, 'sku' => $sku],
                ['discount_rate' => $discountRate]
            );
            $items++;
        }

        fclose($handle);

        return [
            'campaigns' => $campaigns,
            'items' => $items,
        ];
    }
}

