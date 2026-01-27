<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockValueReportService
{
    public function get(User $user): array
    {
        $query = Product::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0);

        $summary = [
            'total_products' => (int) (clone $query)->count(),
            'total_sales_amount' => (float) (clone $query)->sum(DB::raw('price * stock_quantity')),
            'total_cost_amount' => (float) (clone $query)->sum(DB::raw('COALESCE(cost_price, 0) * stock_quantity')),
            'highest_stock' => (clone $query)->orderByDesc('stock_quantity')->first(['name', 'stock_quantity']),
            'lowest_stock' => (clone $query)->orderBy('stock_quantity')->first(['name', 'stock_quantity']),
        ];

        $products = (clone $query)
            ->orderByDesc('stock_quantity')
            ->get(['name', 'sku', 'price', 'cost_price', 'stock_quantity']);

        $table = $products->map(function ($product) {
            $stockCost = (float) ($product->cost_price ?? 0) * (int) $product->stock_quantity;
            $salesTotal = (float) $product->price * (int) $product->stock_quantity;

            return [
                'name' => $product->name,
                'sku' => $product->sku,
                'cost_price' => (float) ($product->cost_price ?? 0),
                'price' => (float) $product->price,
                'stock_quantity' => (int) $product->stock_quantity,
                'stock_cost' => $stockCost,
                'sales_total' => $salesTotal,
            ];
        });

        return [
            'summary' => $summary,
            'table' => $table,
        ];
    }
}
