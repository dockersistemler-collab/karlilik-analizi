<?php

namespace Database\Seeders;

use App\Models\CommunicationMessage;
use App\Models\CommunicationSetting;
use App\Models\CommunicationSlaRule;
use App\Models\CommunicationTemplate;
use App\Models\CommunicationThread;
use App\Models\Marketplace;
use App\Models\MarketplaceStore;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommunicationCenterSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'trendyol', 'name' => 'Trendyol'],
            ['code' => 'hepsiburada', 'name' => 'Hepsiburada'],
            ['code' => 'amazon', 'name' => 'Amazon'],
            ['code' => 'n11', 'name' => 'N11'],
        ] as $marketplace) {
            Marketplace::query()->updateOrCreate(
                ['code' => $marketplace['code']],
                [
                    'name' => $marketplace['name'],
                    'is_active' => true,
                ]
            );
        }

        foreach ([
            'question' => 120,
            'message' => 240,
            'review' => 1440,
        ] as $channel => $minutes) {
            CommunicationSlaRule::query()->updateOrCreate(
                ['marketplace_id' => null, 'channel' => $channel],
                ['sla_minutes' => $minutes, 'is_active' => true]
            );
        }

        CommunicationSetting::query()->updateOrCreate(
            ['user_id' => null],
            [
                'ai_enabled' => true,
                'notification_email' => null,
                'cron_interval_minutes' => 5,
                'priority_weights' => [
                    'time_left' => 3,
                    'store_rating_risk' => 0,
                    'sales_velocity' => 0,
                    'margin' => 0,
                    'buybox_critical' => 0,
                ],
            ]
        );

        $user = User::query()->where('role', 'client')->first();
        if (!$user) {
            return;
        }

        $stores = Marketplace::query()
            ->whereIn('code', ['trendyol', 'hepsiburada'])
            ->get()
            ->map(function (Marketplace $marketplace) use ($user) {
                return MarketplaceStore::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'marketplace_id' => $marketplace->id,
                        'store_external_id' => 'seed-' . $marketplace->code . '-' . $user->id,
                    ],
                    [
                        'store_name' => $marketplace->name . ' Mağaza',
                        'credentials' => [
                            'api_key' => 'stub-key',
                            'secret' => 'stub-secret',
                        ],
                        'is_active' => true,
                    ]
                );
            });

        CommunicationTemplate::query()->updateOrCreate(
            ['user_id' => null, 'title' => 'Kargo Bilgilendirme'],
            [
                'category' => 'shipping',
                'body' => 'Merhaba, siparişiniz hazırlanıyor ve kısa süre içinde kargoya teslim edilecektir.',
                'marketplaces' => ['trendyol', 'hepsiburada', 'amazon', 'n11'],
                'is_active' => true,
            ]
        );

        $store = $stores->first();
        if (!$store) {
            return;
        }

        $thread = CommunicationThread::query()->updateOrCreate(
            [
                'marketplace_store_id' => $store->id,
                'channel' => 'question',
                'external_thread_id' => 'seed-thread-001',
            ],
            [
                'marketplace_id' => $store->marketplace_id,
                'subject' => 'Ürün ölçü bilgisi',
                'product_name' => 'Siyah Spor Ayakkabı',
                'product_sku' => 'SKU-1001',
                'customer_name' => 'Demo Müşteri',
                'status' => 'open',
                'priority_score' => 20,
                'last_inbound_at' => now()->subMinutes(40),
                'due_at' => now()->addMinutes(80),
                'meta' => ['seeded' => true],
            ]
        );

        CommunicationMessage::query()->updateOrCreate(
            [
                'thread_id' => $thread->id,
                'direction' => 'inbound',
                'body' => 'Merhaba, bu ürünün kalıbı dar mı?',
            ],
            [
                'sender_type' => 'customer',
                'created_at_external' => now()->subMinutes(40),
                'meta' => ['seeded' => true],
            ]
        );
    }
}

