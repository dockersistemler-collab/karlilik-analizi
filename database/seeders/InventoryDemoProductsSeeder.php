<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class InventoryDemoProductsSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()
            ->where('role', 'client')
            ->orderByDesc('id')
            ->first();

        if (!$user) {
            $this->command?->warn('Client kullanici bulunamadi, urun eklenmedi.');
            return;
        }

        $existing = Product::query()->where('user_id', $user->id)->count();
        $brands = ['Atlas', 'Nova', 'Zenith', 'Luna', 'Pioneer'];
        $categories = ['Elektronik', 'Ev', 'Yasam', 'Spor', 'Kirtasiye'];

        for ($i = 1; $i <= 100; $i++) {
            $number = $existing + $i;
            $sku = sprintf('STK-%05d', $number);

            Product::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'sku' => $sku,
                ],
                [
                    'name' => sprintf('Stok Demo Urun %03d', $number),
                    'barcode' => sprintf('868000%06d', $number),
                    'description' => sprintf('Stok takip test urunu %03d', $number),
                    'brand' => $brands[$number % count($brands)],
                    'category' => $categories[$number % count($categories)],
                    'price' => 99 + $number,
                    'cost_price' => 49 + $number,
                    'stock_quantity' => 10 + ($number % 90),
                    'critical_stock_level' => 5,
                    'currency' => 'TRY',
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('100 adet demo urun eklendi.');
    }
}

