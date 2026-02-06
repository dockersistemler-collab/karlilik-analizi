<?php

namespace App\Listeners;

use App\Events\PaymentFailed;
use App\Models\MailLog;
use App\Models\User;
use App\Services\Mail\MailSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

class SendPaymentFailedMail implements ShouldQueue
{
    public function __construct(private readonly MailSender $sender)
    {
    }

    public function handle(PaymentFailed $event): void
    {
        $user = User::query()->find($event->userId);
        if (!$user) {
            return;
        }

        if ($this->isDeduped($event, $user->id)) {
            if (!$this->hasRecentDedupedLog($event, $user->id)) {
                MailLog::create([
                    'key' => 'payment.failed',
                    'user_id' => $user->id,
                    'status' => 'deduped',
                    'provider_message_id' => null,
                    'error' => null,
                    'metadata_json' => [
                        'subscription_id' => $event->subscriptionId,
                        'invoice_id' => $event->invoiceId,
                        'provider' => $event->provider,
                        'error_code' => $event->errorCode,
                        'occurred_at' => $event->occurredAt,
                        'reason' => 'dedupe_error_code_30min',
                    ],
                    'sent_at' => null,
                ]);
            }
            return;
        }
$retryUrl = Route::has('portal.addons.index') ? route('portal.addons.index') : null;
        $billingSettingsUrl = Route::has('portal.settings.index') ? route('portal.settings.index') : null;

        $this->sender->send('payment.failed', $user, [
            'user_name' => $user->name,
            'amount' => $event->amount,
            'currency' => $event->currency,
            'error_message' => $event->errorMessage,
            'retry_url' => $retryUrl,
            'billing_settings_url' => $billingSettingsUrl,
        ], [
            'subscription_id' => $event->subscriptionId,
            'invoice_id' => $event->invoiceId,
            'provider' => $event->provider,
            'error_code' => $event->errorCode,
            'occurred_at' => $event->occurredAt,
        ]);
    }

    private function isDeduped(PaymentFailed $event, int $userId): bool
    {
        if ($event->errorCode === null || $event->errorCode === '') {
            return false;
        }
$since = Carbon::now()->subMinutes(30);

        return MailLog::query()
            ->where('key', 'payment.failed')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->where('metadata_json->error_code', $event->errorCode)
            ->exists();
    }

    private function hasRecentDedupedLog(PaymentFailed $event, int $userId): bool
    {
        if ($event->errorCode === null || $event->errorCode === '') {
            return false;
        }
$since = Carbon::now()->subMinutes(30);

        return MailLog::query()
            ->where('key', 'payment.failed')
            ->where('user_id', $userId)
            ->where('status', 'deduped')
            ->where('created_at', '>=', $since)
            ->where('metadata_json->error_code', $event->errorCode)
            ->exists();
    }
}




