<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockValueReportService
{
    public function get(User $user, array $filters = [], int $perPage = 25): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $stockMin = $filters['stock_min'] ?? null;
        $stockMax = $filters['stock_max'] ?? null;
        $sortBy = (string) ($filters['sort_by'] ?? 'stock_desc');

        $query = Product::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0);

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        if ($stockMin !== null && $stockMin !== '') {
            $query->where('stock_quantity', '>=', (int) $stockMin);
        }

        if ($stockMax !== null && $stockMax !== '') {
            $query->where('stock_quantity', '<=', (int) $stockMax);
        }

        switch ($sortBy) {
            case 'stock_asc':
                $query->orderBy('stock_quantity');
                break;
            case 'sales_desc':
                $query->orderByRaw('price * stock_quantity DESC');
                break;
            case 'cost_desc':
                $query->orderByRaw('COALESCE(cost_price, 0) * stock_quantity DESC');
                break;
            case 'name_asc':
                $query->orderBy('name');
                break;
            case 'stock_desc':
            default:
                $query->orderByDesc('stock_quantity');
                break;
        }

        $summary = [
            'total_products' => (int) (clone $query)->count(),
            'total_sales_amount' => (float) (clone $query)->sum(DB::raw('price * stock_quantity')),
            'total_cost_amount' => (float) (clone $query)->sum(DB::raw('COALESCE(cost_price, 0) * stock_quantity')),
            'highest_stock' => (clone $query)->orderByDesc('stock_quantity')->first(['name', 'stock_quantity']),
            'lowest_stock' => (clone $query)->orderBy('stock_quantity')->first(['name', 'stock_quantity']),
        ];

        $table = (clone $query)
            ->select(['name', 'sku', 'price', 'cost_price', 'stock_quantity', 'image_url', 'images'])
            ->paginate($perPage)
            ->withQueryString();

        $table->setCollection($table->getCollection()->map(function ($product) {
            $stockCost = (float) ($product->cost_price ?? 0) * (int) $product->stock_quantity;
            $salesTotal = (float) $product->price * (int) $product->stock_quantity;

            return [
                'name' => $product->name,
                'sku' => $product->sku,
                'image_url' => $product->display_image_url,
                'cost_price' => (float) ($product->cost_price ?? 0),
                'price' => (float) $product->price,
                'stock_quantity' => (int) $product->stock_quantity,
                'stock_cost' => $stockCost,
                'sales_total' => $salesTotal,
            ];
        }));

        return [
            'summary' => $summary,
            'table' => $table,
        ];
    }
}
