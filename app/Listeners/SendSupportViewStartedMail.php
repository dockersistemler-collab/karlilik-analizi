<?php

namespace App\Listeners;

use App\Events\SupportViewStarted;
use App\Models\SupportAccessLog;
use App\Models\User;
use App\Services\Mail\MailSender;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendSupportViewStartedMail implements ShouldQueue
{
    public function __construct(private readonly MailSender $sender)
    {
    }

    public function handle(SupportViewStarted $event): void
    {
        $user = User::query()->find($event->userId);
        if (!$user) {
            return;
        }
$admin = User::query()->find($event->adminId);
        $log = SupportAccessLog::query()->find($event->supportAccessLogId);

        $this->sender->send('security.support_view_used', $user, [
            'user_name' => $user->name,
            'admin_name' => $admin?->name ?? 'Admin',
            'reason' => $event->reason,
            'started_at' => $event->startedAt,
            'log_id' => $event->supportAccessLogId,
        ], [
            'support_access_log_id' => $event->supportAccessLogId,
            'admin_id' => $event->adminId,
            'reason' => $event->reason,
            'ip' => $log?->ip, 'user_agent' => $log?->user_agent,
        ]);
    }
}
