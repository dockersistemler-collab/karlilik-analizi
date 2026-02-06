<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Models\MailLog;
use App\Models\User;
use App\Services\Mail\MailSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

class SendPaymentSucceededMail implements ShouldQueue
{
    public function __construct(private readonly MailSender $sender)
    {
    }

    public function handle(PaymentSucceeded $event): void
    {
        $user = User::query()->find($event->userId);
        if (!$user) {
            return;
        }

        if ($this->isDeduped($event, $user->id)) {
            if (!$this->hasDedupedLog($event, $user->id)) {
                MailLog::create([
                    'key' => 'payment.succeeded',
                    'user_id' => $user->id,
                    'status' => 'deduped',
                    'provider_message_id' => null,
                    'error' => null,
                    'metadata_json' => [
                        'subscription_id' => $event->subscriptionId,
                        'invoice_id' => $event->invoiceId,
                        'provider' => $event->provider,
                        'transaction_id' => $event->transactionId,
                        'occurred_at' => $event->occurredAt,
                        'reason' => $event->transactionId ? 'dedupe_transaction_id' : 'dedupe_occurred_at_minute',
                    ],
                    'sent_at' => null,
                ]);
            }
            return;
        }
$receiptUrl = null;
        $billingUrl = Route::has('portal.settings.index') ? route('portal.settings.index') : null;
        $dashboardUrl = Route::has('portal.dashboard') ? route('portal.dashboard') : null;

        $this->sender->send('payment.succeeded', $user, [
            'user_name' => $user->name,
            'amount' => $event->amount,
            'currency' => $event->currency,
            'provider' => $event->provider,
            'transaction_id' => $event->transactionId,
            'occurred_at' => $event->occurredAt,
            'receipt_url' => $receiptUrl,
            'billing_url' => $billingUrl,
            'dashboard_url' => $dashboardUrl,
        ], [
            'subscription_id' => $event->subscriptionId,
            'invoice_id' => $event->invoiceId,
            'provider' => $event->provider,
            'occurred_at' => $event->occurredAt,
            'transaction_id' => $event->transactionId,
        ]);
    }

    private function isDeduped(PaymentSucceeded $event, int $userId): bool
    {
        if ($event->transactionId) {
            return MailLog::query()
                ->where('key', 'payment.succeeded')
                ->where('user_id', $userId)
                ->where('metadata_json->transaction_id', $event->transactionId)
                ->exists();
        }
$occurredAt = Carbon::parse($event->occurredAt);
        return MailLog::query()
            ->where('key', 'payment.succeeded')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $occurredAt->copy()->startOfMinute())
            ->where('created_at', '<=', $occurredAt->copy()->endOfMinute())
            ->where('status', 'success')
            ->exists();
    }

    private function hasDedupedLog(PaymentSucceeded $event, int $userId): bool
    {
        if ($event->transactionId) {
            return MailLog::query()
                ->where('key', 'payment.succeeded')
                ->where('user_id', $userId)
                ->where('status', 'deduped')
                ->where('metadata_json->transaction_id', $event->transactionId)
                ->exists();
        }
$occurredAt = Carbon::parse($event->occurredAt);
        return MailLog::query()
            ->where('key', 'payment.succeeded')
            ->where('user_id', $userId)
            ->where('status', 'deduped')
            ->where('created_at', '>=', $occurredAt->copy()->startOfMinute())
            ->where('created_at', '<=', $occurredAt->copy()->endOfMinute())
            ->exists();
    }
}




