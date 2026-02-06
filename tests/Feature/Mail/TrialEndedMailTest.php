<?php

namespace Tests\Feature\Mail;

use App\Events\TrialEnded;
use App\Mail\TemplateMailable;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TrialEndedMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_ended_dispatches_mail_and_logs(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'trial.ended',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Deneme süreniz sona erdi — Paketinizi seçin',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $plan = Plan::create([
            'name' => 'Trial',
            'slug' => 'trial',
            'description' => 'Trial plan',
            'price' => 0,
            'yearly_price' => null,
            'billing_period' => 'monthly',
            'max_products' => 0,
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

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->subMinute(),
            'cancelled_at' => null,
            'amount' => 0,
            'billing_period' => 'monthly',
            'auto_renew' => false,
            'current_products_count' => 0,
            'current_marketplaces_count' => 0,
            'current_month_orders_count' => 0,
            'usage_reset_at' => now()->addMonth(),
        ]);

        Artisan::call('subscriptions:maintain');

        Mail::assertQueued(TemplateMailable::class);

        $log = MailLog::query()
            ->where('key', 'trial.ended')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($subscription->id, $log->metadata_json['subscription_id'] ?? null);
    }

    public function test_trial_ended_dedupes_within_24_hours(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'trial.ended',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Deneme süreniz sona erdi — Paketinizi seçin',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        event(new TrialEnded(
            $user->id,
            123,
            1,
            now()->subDays(7)->toDateTimeString(),
            now()->toDateTimeString(),
            now()->toDateTimeString()
        ));

        event(new TrialEnded(
            $user->id,
            123,
            1,
            now()->subDays(7)->toDateTimeString(),
            now()->toDateTimeString(),
            now()->toDateTimeString()
        ));

        Mail::assertQueued(TemplateMailable::class, 1);

        $successCount = MailLog::query()
            ->where('key', 'trial.ended')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->count();

        $dedupedCount = MailLog::query()
            ->where('key', 'trial.ended')
            ->where('user_id', $user->id)
            ->where('status', 'deduped')
            ->count();

        $this->assertSame(1, $successCount);
        $this->assertSame(1, $dedupedCount);
    }
}
