<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Başlangıç',
                'slug' => 'starter',
                'description' => 'Küçük işletmeler için ideal başlangıç paketi',
                'price' => 299.00,
                'yearly_price' => 2990.00, // 2 ay bedava
                'billing_period' => 'monthly',
                'max_products' => 100,
                'max_marketplaces' => 2,
                'max_orders_per_month' => 500,
                'max_tickets_per_month' => 20,
                'api_access' => false,
                'advanced_reports' => false,
                'priority_support' => false,
                'custom_integrations' => false,
                'features' => [
                    'Temel ürün yönetimi',
                    'Stok senkronizasyonu',
                    'Sipariş takibi',
                    'E-posta desteği',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Profesyonel',
                'slug' => 'professional',
                'description' => 'Büyüyen işletmeler için gelişmiş özellikler',
                'price' => 599.00,
                'yearly_price' => 5990.00,
                'billing_period' => 'monthly',
                'max_products' => 500,
                'max_marketplaces' => 5,
                'max_orders_per_month' => 2000,
                'max_tickets_per_month' => 100,
                'api_access' => true,
                'advanced_reports' => true,
                'priority_support' => false,
                'custom_integrations' => false,
                'features' => [
                    'Gelişmiş ürün yönetimi',
                    'Otomatik stok senkronizasyonu',
                    'Gelişmiş sipariş yönetimi',
                    'API erişimi',
                    'Gelişmiş raporlar',
                    'Toplu işlemler',
                    'E-posta ve chat desteği',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Kurumsal',
                'slug' => 'enterprise',
                'description' => 'Sınırsız özelliklerle kurumsal çözüm',
                'price' => 1499.00,
                'yearly_price' => 14990.00,
                'billing_period' => 'monthly',
                'max_products' => 0, // Unlimited
                'max_marketplaces' => 0, // Unlimited
                'max_orders_per_month' => 0, // Unlimited
                'max_tickets_per_month' => 0, // Unlimited
                'api_access' => true,
                'advanced_reports' => true,
                'priority_support' => true,
                'custom_integrations' => true,
                'features' => [
                    'Sınırsız ürün',
                    'Sınırsız pazaryeri',
                    'Sınırsız sipariş',
                    'API erişimi',
                    'Gelişmiş raporlar ve analizler',
                    'Öncelikli destek',
                    'Özel entegrasyonlar',
                    'Toplu işlemler',
                    'Çoklu kullanıcı yönetimi',
                    '7/24 destek',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
