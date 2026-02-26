<?php

namespace App\Services\ActionEngine;

use App\Models\ActionRecommendation;

class RecommendationWriter
{
    public function write(int $tenantId, int $userId, array $payload): array
    {
        $date = (string) $payload['date'];
        $actionType = (string) $payload['action_type'];
        $marketplace = strtolower((string) $payload['marketplace']);
        $sku = (string) ($payload['sku'] ?? '');

        $existing = ActionRecommendation::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', $date)
            ->where('action_type', $actionType)
            ->where('marketplace', $marketplace)
            ->where('sku', $sku)
            ->first();
        if ($existing && in_array((string) $existing->status, ['applied', 'dismissed'], true)) {
            return ['created' => false, 'updated' => false, 'skipped' => true, 'model' => $existing];
        }

        if ($existing) {
            $existing->update([
                'user_id' => $userId,
                'severity' => (string) ($payload['severity'] ?? 'medium'),
                'title' => (string) ($payload['title'] ?? 'Aksiyon onerisi'),
                'description' => (string) ($payload['description'] ?? ''),
                'suggested_payload' => $payload['suggested_payload'] ?? null,
                'reason' => $payload['reason'] ?? null,
                'status' => 'open',
                'decided_at' => null,
                'decided_by' => null,
            ]);

            return ['created' => false, 'updated' => true, 'skipped' => false, 'model' => $existing];
        }

        $model = ActionRecommendation::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'date' => $date,
            'marketplace' => $marketplace,
            'sku' => $sku,
            'severity' => (string) ($payload['severity'] ?? 'medium'),
            'title' => (string) ($payload['title'] ?? 'Aksiyon onerisi'),
            'description' => (string) ($payload['description'] ?? ''),
            'action_type' => $actionType,
            'suggested_payload' => $payload['suggested_payload'] ?? null,
            'reason' => $payload['reason'] ?? null,
            'status' => 'open',
        ]);

        return ['created' => true, 'updated' => false, 'skipped' => false, 'model' => $model];
    }
}
