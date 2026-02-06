<?php

namespace Tests\Feature\Mail;

use App\Events\SubscriptionRenewed;
use App\Mail\TemplateMailable;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubscriptionRenewedMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_renewed_dispatches_mail_and_logs(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'subscription.renewed',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Aboneliginiz yenilendi',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        event(new SubscriptionRenewed(
            $user->id,
            123,
            1,
            'Basic',
            now()->toDateTimeString(),
            now()->toDateTimeString(),
            now()->addMonth()->toDateTimeString(),
            '99.90',
            'TRY',
            now()->toDateTimeString()
        ));

        Mail::assertQueued(TemplateMailable::class);

        $log = MailLog::query()
            ->where('key', 'subscription.renewed')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(123, $log->metadata_json['subscription_id'] ?? null);
    }

    public function test_subscription_renewed_dedupes_by_subscription_and_period(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'subscription.renewed',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Aboneliginiz yenilendi',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $periodEnd = now()->addMonth()->toDateTimeString();
        $renewedAt = now()->toDateTimeString();

        event(new SubscriptionRenewed(
            $user->id,
            456,
            1,
            'Basic',
            $renewedAt,
            null,
            $periodEnd,
            '99.90',
            'TRY',
            now()->toDateTimeString()
        ));

        event(new SubscriptionRenewed(
            $user->id,
            456,
            1,
            'Basic',
            $renewedAt,
            null,
            $periodEnd,
            '99.90',
            'TRY',
            now()->toDateTimeString()
        ));

        Mail::assertQueued(TemplateMailable::class, 1);

        $successCount = MailLog::query()
            ->where('key', 'subscription.renewed')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->count();

        $dedupedCount = MailLog::query()
            ->where('key', 'subscription.renewed')
            ->where('user_id', $user->id)
            ->where('status', 'deduped')
            ->count();

        $this->assertSame(1, $successCount);
        $this->assertSame(1, $dedupedCount);
    }
}
