<?php

namespace Database\Seeders;

use App\Models\Marketplace;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoOrdersFromStockSeeder extends Seeder
{
    public function run(): void
    {
        $globalStockProducts = Product::query()
            ->where('stock_quantity', '>', 0)
            ->where(function ($q): void {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->get(['id', 'sku', 'name', 'price', 'stock_quantity', 'image_url']);

        if ($globalStockProducts->isEmpty()) {
            $this->command?->warn('Stoklu urun bulunamadi.');
            return;
        }

        $targetUsers = User::query()
            ->where('role', 'client')
            ->where('is_active', true)
            ->get(['id', 'tenant_id']);

        if ($targetUsers->isEmpty()) {
            $this->command?->warn('Demo siparis icin aktif musteri kullanici bulunamadi.');
            return;
        }

        $marketplaceIds = Marketplace::query()
            ->where('is_active', true)
            ->pluck('id')
            ->values();

        $created = 0;

        foreach ($targetUsers as $user) {
            $userProducts = Product::query()
                ->where('user_id', $user->id)
                ->where('stock_quantity', '>', 0)
                ->where(function ($q): void {
                    $q->where('is_active', true)->orWhereNull('is_active');
                })
                ->get(['id', 'sku', 'name', 'price', 'stock_quantity', 'image_url']);

            $products = $userProducts->isNotEmpty() ? $userProducts : $globalStockProducts;
            $targetCount = min(20, max(8, min(12, $products->count() * 2)));

            for ($i = 0; $i < $targetCount; $i++) {
                $product = $products->random();
                $qty = max(1, min((int) ($product->stock_quantity ?: 1), random_int(1, 3)));
                $status = random_int(0, 1) === 1 ? 'pending' : 'approved';
                $unitPrice = max(1.0, (float) ($product->price ?: random_int(50, 500)));
                $total = round($unitPrice * $qty, 2);
                $commission = round($total * 0.12, 2);
                $net = round($total - $commission, 2);
                $orderDate = now()->subDays(random_int(0, 15))->subMinutes(random_int(0, 1439));

                Order::query()->create([
                    'tenant_id' => $user->tenant_id,
                    'user_id' => $user->id,
                    'marketplace_id' => $marketplaceIds->isNotEmpty() ? $marketplaceIds->random() : null,
                    'marketplace_order_id' => 'DEMO-' . $user->id . '-' . now()->format('YmdHis') . '-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT) . '-' . random_int(100, 999),
                    'order_number' => 'DM' . now()->format('md') . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                    'status' => $status,
                    'total_amount' => $total,
                    'commission_amount' => $commission,
                    'net_amount' => $net,
                    'currency' => 'TRY',
                    'customer_name' => 'Demo Musteri ' . ($i + 1),
                    'customer_email' => 'demo' . ($i + 1) . '@example.com',
                    'customer_phone' => '05' . random_int(300000000, 599999999),
                    'shipping_address' => 'Demo Mah. Test Cad. No:' . random_int(1, 120) . ' Istanbul',
                    'billing_address' => 'Demo Mah. Test Cad. No:' . random_int(1, 120) . ' Istanbul',
                    'order_date' => $orderDate,
                    'approved_at' => $status === 'approved' ? $orderDate->copy()->addMinutes(random_int(10, 180)) : null,
                    'items' => [[
                        'sku' => (string) ($product->sku ?: 'DEMO-SKU'),
                        'stock_code' => (string) ($product->sku ?: 'DEMO-SKU'),
                        'name' => (string) ($product->name ?: 'Demo Urun'),
                        'quantity' => $qty,
                        'price' => $unitPrice,
                        'image_url' => $product->image_url,
                    ]],
                ]);

                $created++;
            }
        }

        $this->command?->info("Demo siparisler olusturuldu: {$created} (kullanici sayisi={$targetUsers->count()})");
    }
}

