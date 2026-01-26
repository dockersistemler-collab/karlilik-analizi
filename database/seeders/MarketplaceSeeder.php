<?php

namespace Database\Seeders;

use App\Models\Marketplace;
use Illuminate\Database\Seeder;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        $marketplaces = [
            [
                'name' => 'Trendyol',
                'code' => 'trendyol',
                'api_url' => 'https://api.trendyol.com',
                'is_active' => true,
                'settings' => [
                    'default_commission' => 15,
                    'currency' => 'TRY',
                ],
            ],
            [
                'name' => 'Hepsiburada',
                'code' => 'hepsiburada',
                'api_url' => 'https://listing-external.hepsiburada.com',
                'is_active' => true,
                'settings' => [
                    'default_commission' => 18,
                    'currency' => 'TRY',
                    'video_url' => '#',
                    'guide_url' => '#',
                    'service_key_help_url' => '#',
                ],
            ],
            [
                'name' => 'N11',
                'code' => 'n11',
                'api_url' => 'https://api.n11.com',
                'is_active' => true,
                'settings' => [
                    'default_commission' => 12,
                    'currency' => 'TRY',
                ],
            ],
            [
                'name' => 'Amazon TR',
                'code' => 'amazon_tr',
                'api_url' => 'https://mws.amazonservices.com.tr',
                'is_active' => true,
                'settings' => [
                    'default_commission' => 15,
                    'currency' => 'TRY',
                ],
            ],
            [
                'name' => 'Çiçek Sepeti',
                'code' => 'ciceksepeti',
                'api_url' => 'https://api.ciceksepeti.com',
                'is_active' => true,
                'settings' => [
                    'default_commission' => 20,
                    'currency' => 'TRY',
                    'video_url' => '#',
                    'guide_url' => '#',
                ],
            ],
        ];

        foreach ($marketplaces as $marketplace) {
            Marketplace::create($marketplace);
        }
    }
}
