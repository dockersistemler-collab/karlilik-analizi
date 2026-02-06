<?php

namespace Tests\Feature\Mail;

use App\Events\SupportViewStarted;
use App\Mail\TemplateMailable;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\SupportAccessLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SupportViewStartedMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_dispatch_sends_mail_and_logs(): void
    {
        Mail::fake();

        MailTemplate::create([
            'key' => 'security.support_view_used',
            'channel' => 'email',
            'category' => 'security',
            'subject' => 'Support View kullanımı',
            'body_html' => '<p>Merhaba {{user_name}}</p>',
            'enabled' => true,
        ]);

        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => 'client',
            'is_active' => true,
        ]);

        $log = SupportAccessLog::create([
            'super_admin_id' => $admin->id,
            'actor_user_id' => $admin->id,
            'actor_role' => 'super_admin',
            'source_type' => 'manual',
            'source_id' => null,
            'target_user_id' => $user->id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(60),
            'ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'reason' => 'Test',
            'scope' => 'read_only',
        ]);

        event(new SupportViewStarted(
            $log->id,
            $user->id,
            $admin->id,
            'Test',
            $log->started_at->toDateTimeString()
        ));

        Mail::assertQueued(TemplateMailable::class);

        $mailLog = MailLog::query()
            ->where('key', 'security.support_view_used')
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->first();

        $this->assertNotNull($mailLog);
        $this->assertSame($log->id, $mailLog->metadata_json['support_access_log_id'] ?? null);
    }
}
