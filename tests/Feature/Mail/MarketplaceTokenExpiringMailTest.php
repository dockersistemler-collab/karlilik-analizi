<?php

namespace Tests\Feature\Mail;

use App\Events\MarketplaceTokenExpiring;
use App\Mail\TemplateMailable;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\Marketplace;
use App\Models\MarketplaceCredential;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MarketplaceTokenExpiringMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_events_for_7_and_3_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-04 08:00:00'));

        Event::fake([MarketplaceTokenExpiring::class]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $marketplace = Marketplace::create([
            'name' => 'Trendyol',
            'code' => 'trendyol',
            'api_url' => 'https://example.test',
            'is_active' => true,
            'settings' => [],
        ]);

        $cred7 = MarketplaceCredential::create([
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'is_active' => true,
            'token_expires_at' => now()->addDays(7)->setTime(10, 0),
        ]);

        $cred3 = MarketplaceCredential::create([
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'is_active' => true,
            'token_expires_at' => now()->addDays(3)->setTime(10, 0),
        ]);

        Artisan::call('marketplace:check-token-expirations');

        Event::assertDispatched(MarketplaceTokenExpiring::class, function (MarketplaceTokenExpiring $event) use ($cred7): bool {
            return $event->marketplaceCredentialId === $cred7->id && $event->daysLeft === 7;
        });

        Event::assertDispatched(MarketplaceTokenExpiring::class, function (MarketplaceTokenExpiring $event) use ($cred3): bool {
            return $event->marketplaceCredentialId === $cred3->id && $event->daysLeft === 3;
        });
    }

    public function test_token_expiring_mail_and_dedupe(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-02-04 08:00:00'));

        MailTemplate::create([
            'key' => 'mp.token_expiring',
            'channel' => 'email',
            'category' => 'marketplace',
            'subject' => 'Test',
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
            'api_url' => 'https://example.test',
            'is_active' => true,
            'settings' => [],
        ]);

        $credential = MarketplaceCredential::create([
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'is_active' => true,
            'token_expires_at' => now()->addDays(7)->setTime(10, 0),
        ]);

        $credentialThree = MarketplaceCredential::create([
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'is_active' => true,
            'token_expires_at' => now()->addDays(3)->setTime(10, 0),
        ]);

        event(new MarketplaceTokenExpiring(
            $user->id,
            $credential->id,
            $marketplace->name,
            now()->addDays(7)->toDateTimeString(),
            7,
            now()->toDateTimeString()
        ));

        event(new MarketplaceTokenExpiring(
            $user->id,
            $credential->id,
            $marketplace->name,
            now()->addDays(7)->toDateTimeString(),
            7,
            now()->toDateTimeString()
        ));

        event(new MarketplaceTokenExpiring(
            $user->id,
            $credentialThree->id,
            $marketplace->name,
            now()->addDays(3)->toDateTimeString(),
            3,
            now()->toDateTimeString()
        ));

        Mail::assertQueued(TemplateMailable::class, 2);

        $successCount = MailLog::query()
            ->where('key', 'mp.token_expiring')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->where('metadata_json->marketplace_credential_id', $credential->id)
            ->count();

        $dedupedCount = MailLog::query()
            ->where('key', 'mp.token_expiring')
            ->where('user_id', $user->id)
            ->where('status', 'deduped')
            ->where('metadata_json->marketplace_credential_id', $credential->id)
            ->count();

        $log3 = MailLog::query()
            ->where('key', 'mp.token_expiring')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->where('metadata_json->marketplace_credential_id', $credentialThree->id)
            ->first();

        $this->assertSame(1, $successCount);
        $this->assertSame(1, $dedupedCount);
        $this->assertNotNull($log3);
        $this->assertSame(3, $log3->metadata_json['days_left'] ?? null);
    }
}
