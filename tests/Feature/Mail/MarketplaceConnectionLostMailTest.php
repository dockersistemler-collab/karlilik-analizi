<?php

namespace Tests\Feature\Mail;

use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\Marketplace;
use App\Models\MarketplaceCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MarketplaceConnectionLostMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_connection_lost_dispatches_mail_and_logs(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'mp.connection_lost',
            'channel' => 'email',
            'category' => 'marketplace',
            'subject' => 'Pazaryeri bağlantısı koptu: {{marketplace}}',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $marketplace = Marketplace::create([
            'name' => 'Trendyol',
            'code' => 'trendyol',
            'api_url' => 'https://api.trendyol.com',
            'is_active' => true,
        ]);

        $credential = MarketplaceCredential::create([
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'store_id' => 'store-1',
            'is_active' => true,
        ]);

        Http::fake([
            'https://api.trendyol.com/*' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $provider = new \App\Services\Marketplace\Category\TrendyolCategoryProvider();

        try {
            $provider->fetchCategoryTree($credential);
        } catch (\RuntimeException $e) {
            // expected
        }

        $log = MailLog::query()
            ->where('key', 'mp.connection_lost')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('trendyol', $log->metadata_json['marketplace'] ?? null);
        $this->assertSame('store-1', $log->metadata_json['store_id'] ?? null);
    }
}
