<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductVariantSeeder extends Seeder
{
    public function run(): void
    {
        Product::query()
            ->withCount('variants')
            ->where('variants_count', 0)
            ->chunkById(200, function ($products) {
                foreach ($products as $product) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'barcode' => $product->barcode,
                        'stock' => $product->stock_quantity ?? 0,
                    ]);
                }
            });
    }
}
