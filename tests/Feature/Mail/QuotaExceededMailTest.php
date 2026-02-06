<?php

namespace Tests\Feature\Mail;

use App\Events\QuotaExceeded;
use App\Mail\TemplateMailable;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuotaExceededMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_quota_exceeded_dispatches_mail_and_logs(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'quota.exceeded',
            'channel' => 'email',
            'category' => 'usage',
            'subject' => 'Kotaniz doldu',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $plan = Plan::create([
            'name' => 'Basic',
            'slug' => 'basic',
            'description' => null,
            'price' => 100,
            'yearly_price' => 1000,
            'billing_period' => 'monthly',
            'max_products' => 1,
            'max_marketplaces' => 0,
            'max_orders_per_month' => 0,
            'max_tickets_per_month' => 0,
            'api_access' => false,
            'advanced_reports' => false,
            'priority_support' => false,
            'custom_integrations' => false,
            'features' => [],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'cancelled_at' => null,
            'amount' => 100,
            'billing_period' => 'monthly',
            'auto_renew' => true,
            'current_products_count' => 1,
            'current_marketplaces_count' => 0,
            'current_month_orders_count' => 0,
            'usage_reset_at' => null,
        ]);

        $this->actingAs($user)
            ->withoutMiddleware()
            ->from(route('portal.products.index'))
            ->post(route('portal.products.store'), [
                'name' => 'Test Product',
                'price' => 100,
                'stock_quantity' => 1,
            ])
            ->assertRedirect(route('portal.products.index'));

        Mail::assertQueued(TemplateMailable::class);

        $log = MailLog::query()
            ->where('key', 'quota.exceeded')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('products', $log->metadata_json['quota_key'] ?? null);
    }

    public function test_quota_exceeded_dedupes_by_key_and_period(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'quota.exceeded',
            'channel' => 'email',
            'category' => 'usage',
            'subject' => 'Kotaniz doldu',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        event(new QuotaExceeded(
            $user->id,
            'orders',
            100,
            101,
            'monthly',
            now()->addDays(10)->toDateTimeString(),
            now()->toDateTimeString()
        ));

        event(new QuotaExceeded(
            $user->id,
            'orders',
            100,
            101,
            'monthly',
            now()->addDays(10)->toDateTimeString(),
            now()->toDateTimeString()
        ));

        Mail::assertQueued(TemplateMailable::class, 1);

        $successCount = MailLog::query()
            ->where('key', 'quota.exceeded')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->count();

        $dedupedCount = MailLog::query()
            ->where('key', 'quota.exceeded')
            ->where('user_id', $user->id)
            ->where('status', 'deduped')
            ->count();

        $this->assertSame(1, $successCount);
        $this->assertSame(1, $dedupedCount);
    }
}

