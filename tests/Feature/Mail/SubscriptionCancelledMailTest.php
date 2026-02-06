<?php

namespace Tests\Feature\Mail;

use App\Events\SubscriptionCancelled;
use App\Mail\TemplateMailable;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubscriptionCancelledMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_cancelled_dispatches_mail_and_logs(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'subscription.cancelled',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Aboneliginiz iptal edildi',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        event(new SubscriptionCancelled(
            $user->id,
            123,
            1,
            'Basic',
            now()->toDateTimeString(),
            now()->addMonth()->toDateTimeString(),
            null,
            now()->toDateTimeString()
        ));

        Mail::assertQueued(TemplateMailable::class);

        $log = MailLog::query()
            ->where('key', 'subscription.cancelled')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(123, $log->metadata_json['subscription_id'] ?? null);
    }

    public function test_subscription_cancelled_dedupes_by_subscription_id(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'subscription.cancelled',
            'channel' => 'email',
            'category' => 'billing',
            'subject' => 'Aboneliginiz iptal edildi',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        event(new SubscriptionCancelled(
            $user->id,
            456,
            1,
            'Basic',
            now()->toDateTimeString(),
            null,
            null,
            now()->toDateTimeString()
        ));

        event(new SubscriptionCancelled(
            $user->id,
            456,
            1,
            'Basic',
            now()->toDateTimeString(),
            null,
            null,
            now()->toDateTimeString()
        ));

        Mail::assertQueued(TemplateMailable::class, 1);

        $successCount = MailLog::query()
            ->where('key', 'subscription.cancelled')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->count();

        $dedupedCount = MailLog::query()
            ->where('key', 'subscription.cancelled')
            ->where('user_id', $user->id)
            ->where('status', 'deduped')
            ->count();

        $this->assertSame(1, $successCount);
        $this->assertSame(1, $dedupedCount);
    }
}
