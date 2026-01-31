<?php

namespace App\Services\Marketplace\Category;

use App\Models\Marketplace;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceCredential;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MarketplaceCategorySyncService
{
    public function syncForCredential(MarketplaceCredential $credential): int
    {
        $credential->loadMissing('marketplace');

        if (!$credential->marketplace) {
            throw new \RuntimeException('Marketplace bulunamadi.');
        }

        $provider = $this->resolveProvider($credential->marketplace);
        $tree = $provider->fetchCategoryTree($credential);

        $now = Carbon::now();
        $flat = $this->flattenTree($tree);

        foreach ($flat as $row) {
            MarketplaceCategory::query()->updateOrCreate(
                [
                    'user_id' => $credential->user_id,
                    'marketplace_id' => $credential->marketplace_id,
                    'external_id' => $row['external_id'],
                ],
                [
                    'parent_external_id' => $row['parent_external_id'],
                    'name' => $row['name'],
                    'path' => $row['path'],
                    'is_leaf' => $row['is_leaf'],
                    'raw' => $row['raw'] ?? null,
                    'synced_at' => $now,
                ]
            );
        }

        return count($flat);
    }

    private function resolveProvider(Marketplace $marketplace): MarketplaceCategoryProviderInterface
    {
        return match ($marketplace->code) {
            'trendyol' => new TrendyolCategoryProvider(),
            default => throw new UnsupportedMarketplaceCategoriesException(
                "{$marketplace->name} icin kategori senkronu henuz eklenmedi."
            ),
        };
    }

    /**
     * @param array<int, array<string, mixed>> $tree
     * @return array<int, array<string, mixed>>
     */
    private function flattenTree(array $tree): array
    {
        $out = [];

        $walk = function (array $nodes, array $parentPath) use (&$walk, &$out) {
            foreach ($nodes as $node) {
                $name = (string) ($node['name'] ?? '');
                $pathParts = array_values(array_filter([...$parentPath, $name], fn ($p) => is_string($p) && $p !== ''));
                $path = implode(' > ', $pathParts);

                $children = $node['children'] ?? [];
                $out[] = [
                    'external_id' => (string) $node['external_id'],
                    'parent_external_id' => $node['parent_external_id'] ?? null,
                    'name' => $name,
                    'path' => $path ?: $name,
                    'is_leaf' => (bool) ($node['is_leaf'] ?? empty($children)),
                    'raw' => $node['raw'] ?? null,
                ];

                if (is_array($children) && count($children) > 0) {
                    $walk($children, $pathParts);
                }
            }
        };

        $walk($tree, []);

        return $out;
    }
}

