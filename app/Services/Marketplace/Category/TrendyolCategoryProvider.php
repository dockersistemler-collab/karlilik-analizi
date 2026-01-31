<?php

namespace App\Services\Marketplace\Category;

use App\Models\MarketplaceCredential;
use Illuminate\Support\Facades\Http;

class TrendyolCategoryProvider implements MarketplaceCategoryProviderInterface
{
    public function fetchCategoryTree(MarketplaceCredential $credential): array
    {
        $marketplace = $credential->marketplace;
        $baseUrl = rtrim($marketplace?->api_url ?: 'https://api.trendyol.com', '/');

        // NOTE: Trendyol category endpoints can vary by API version.
        // We keep this implementation defensive and normalize common tree shapes.
        $response = Http::timeout(20)
            ->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'pazaryeri-entegrasyon/1.0',
            ])
            ->get($baseUrl . '/sapigw/product-categories');

        $data = $response->json();
        if (!$response->successful() || !$data) {
            throw new \RuntimeException('Trendyol kategorileri cekilemedi. HTTP: ' . $response->status());
        }

        $nodes = $data['categories'] ?? $data['content'] ?? $data;
        if (!is_array($nodes)) {
            throw new \RuntimeException('Trendyol kategori verisi beklenmeyen formatta.');
        }

        return $this->normalizeNodes($nodes, null);
    }

    /**
     * @param array<int, mixed> $nodes
     * @return array<int, array<string, mixed>>
     */
    private function normalizeNodes(array $nodes, ?string $parentExternalId): array
    {
        $out = [];
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            $externalId = $node['id'] ?? $node['categoryId'] ?? $node['code'] ?? null;
            if ($externalId === null) {
                continue;
            }
            $externalId = (string) $externalId;

            $name = $node['name'] ?? $node['title'] ?? null;
            if (!is_string($name) || $name === '') {
                $name = $externalId;
            }

            $childrenRaw = $node['subCategories'] ?? $node['children'] ?? [];
            $children = is_array($childrenRaw) ? $this->normalizeNodes($childrenRaw, $externalId) : [];

            $out[] = [
                'external_id' => $externalId,
                'parent_external_id' => $parentExternalId,
                'name' => $name,
                'children' => $children,
                'is_leaf' => empty($children),
                'raw' => $node,
            ];
        }

        return $out;
    }
}

