<?php

namespace App\Jobs;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Mail\NotificationHubMail;
use App\Models\Notification;
use App\Models\User;
use App\Services\Notifications\EmailSuppressionService;
use App\Services\Notifications\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

class SendNotificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public string $notificationId)
    {
    }

    /**
     * @return array<int,int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(NotificationService $service, EmailSuppressionService $suppressions): void
    {
        $notification = Notification::query()->find($this->notificationId);
        if (!$notification || $notification->channel !== NotificationChannel::Email->value) {
            return;
        }
$userId = $notification->user_id ?: $notification->tenant_id;
        $user = User::query()->find($userId);
        if (!$user) {
            return;
        }
$quiet = $service->resolveQuietHours($user, NotificationType::from($notification->type), $notification->marketplace);
        if ($quiet) {
            $releaseAt = $service->nextQuietHoursReleaseAt($quiet, Carbon::now());
            if ($releaseAt) {
                $delaySeconds = $releaseAt->diffInSeconds(Carbon::now()) ?: 60;
                $this->release($delaySeconds);
                $service->logEmailAudit($notification, 'email_deferred', [
                    'delay_seconds' => $delaySeconds,
                    'release_at' => $releaseAt->toIso8601String(),
                ]);
                return;
            }
        }
$recipient = $user->notification_email ?: $user->email;
        if (!$recipient) {
            return;
        }

        try {
            Mail::to($recipient)->send(new NotificationHubMail($notification));
        } catch (TransportExceptionInterface | Throwable $e) {
            $service->logEmailAudit($notification, 'email_failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            if ($this->isHardFail($e)) {
                $suppression = $suppressions->suppress($notification->tenant_id,
                    $recipient,
                    'hard_fail',
                    'smtp',
                    [
                        'exception' => $e::class,
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ]
                );

                $service->logEmailAudit($notification, 'email_suppressed', [
                    'reason' => $suppression->reason,
                    'suppression_id' => $suppression->id,
                    'source' => $suppression->source,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);

                return;
            }

            throw $e;
        }
$service->logEmailAudit($notification, 'email_dispatched', [
            'recipient' => $recipient,
        ]);
    }

    private function isHardFail(Throwable $e): bool
    {
        $code = (int) $e->getCode();
        if ($code >= 500 && $code < 600) {
            return true;
        }
$message = strtolower($e->getMessage());
        $hardIndicators = [
            'user unknown',
            'no such user',
            'unknown user',
            'user not found',
            'mailbox unavailable',
            'mailbox not found',
            'invalid recipient',
            'address rejected',
            'recipient rejected',
            'no such mailbox',
            '550',
            '551',
            '552',
            '553',
            '554',
        ];

        foreach ($hardIndicators as $indicator) {
            if (str_contains($message, $indicator)) {
                return true;
            }
        }

        if (preg_match('/\\b5\\d\\d\\b/', $message)) {
            return true;
        }

        return false;
    }
}
