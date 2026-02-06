<?php

namespace Tests\Feature\Mail;

use App\Events\QuotaWarningReached;
use App\Mail\TemplateMailable;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuotaWarningReachedMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_quota_warning_dispatches_mail_and_logs(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'quota.warning_80',
            'channel' => 'email',
            'category' => 'usage',
            'subject' => 'Test',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        event(new QuotaWarningReached(
            $user->id,
            'products',
            80,
            100,
            80,
            null,
            now()->toDateTimeString()
        ));

        Mail::assertQueued(TemplateMailable::class, 1);

        $log = MailLog::query()
            ->where('key', 'quota.warning_80')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('products', $log->metadata_json['quota_type'] ?? null);
    }

    public function test_quota_warning_dedupes_by_type_and_period(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'quota.warning_80',
            'channel' => 'email',
            'category' => 'usage',
            'subject' => 'Test',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $period = '2026-02';
        $previousPeriod = '2026-01';

        event(new QuotaWarningReached(
            $user->id,
            'orders_per_month',
            80,
            100,
            80,
            $period,
            now()->toDateTimeString()
        ));

        event(new QuotaWarningReached(
            $user->id,
            'orders_per_month',
            81,
            100,
            80,
            $period,
            now()->toDateTimeString()
        ));

        event(new QuotaWarningReached(
            $user->id,
            'orders_per_month',
            80,
            100,
            80,
            $previousPeriod,
            now()->toDateTimeString()
        ));

        Mail::assertQueued(TemplateMailable::class, 2);

        $logs = MailLog::query()
            ->where('key', 'quota.warning_80')
            ->where('user_id', $user->id)
            ->get();

        // Debug helper when needed:
        // Debug helper when needed:
        // foreach ($logs as $log) {
        //     fwrite(STDERR, sprintf("[quota] %s %s\n", $log->status, $log->metadata_json['period'] ?? 'null'));
        // }

        $successCurrent = $logs->filter(function ($log) use ($period) {
            return $log->status === 'success' && ($log->metadata_json['period'] ?? null) === $period;
        })->count();

        $successPrevious = $logs->filter(function ($log) use ($previousPeriod) {
            return $log->status === 'success' && ($log->metadata_json['period'] ?? null) === $previousPeriod;
        })->count();

        $dedupedCurrent = $logs->filter(function ($log) use ($period) {
            return $log->status === 'deduped' && ($log->metadata_json['period'] ?? null) === $period;
        })->count();

        $this->assertSame(1, $successCurrent);
        $this->assertSame(1, $successPrevious);
        $this->assertSame(1, $dedupedCurrent);
    }
}
