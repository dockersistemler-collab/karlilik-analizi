<?php

namespace App\Listeners;

use App\Events\InvoiceFailed;
use App\Models\MailLog;
use App\Models\User;
use App\Services\Mail\MailSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

class SendInvoiceFailedMail implements ShouldQueue
{
    public function __construct(private readonly MailSender $sender)
    {
    }

    public function handle(InvoiceFailed $event): void
    {
        $user = User::query()->find($event->userId);
        if (!$user) {
            return;
        }

        if ($this->isDeduped($event, $user->id)) {
            if (!$this->hasRecentDedupedLog($event, $user->id)) {
                MailLog::create([
                    'key' => 'invoice.failed',
                    'user_id' => $user->id,
                    'status' => 'deduped',
                    'provider_message_id' => null,
                    'error' => null,
                    'metadata_json' => [
                        'invoice_id' => $event->invoiceId,
                        'order_id' => $event->orderId,
                        'marketplace' => $event->marketplace,
                        'error_code' => $event->errorCode,
                        'occurred_at' => $event->occurredAt,
                        'reason' => 'dedupe_order_60min',
                    ],
                    'sent_at' => null,
                ]);
            }
            return;
        }
$retryUrl = Route::has('portal.einvoices.index') ? route('portal.einvoices.index') : null;
        $supportUrl = Route::has('portal.help.support') ? route('portal.help.support') : null;

        $this->sender->send('invoice.failed', $user, [
            'user_name' => $user->name,
            'order_id' => $event->orderId,
            'marketplace' => $event->marketplace,
            'error_message' => $event->errorMessage,
            'retry_url' => $retryUrl,
            'support_url' => $supportUrl,
        ], [
            'invoice_id' => $event->invoiceId,
            'order_id' => $event->orderId,
            'marketplace' => $event->marketplace,
            'error_code' => $event->errorCode,
            'occurred_at' => $event->occurredAt,
        ]);
    }

    private function isDeduped(InvoiceFailed $event, int $userId): bool
    {
        if ($event->orderId === null) {
            return false;
        }
$since = Carbon::now()->subMinutes(60);

        return MailLog::query()
            ->where('key', 'invoice.failed')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->where('metadata_json->order_id', $event->orderId)
            ->exists();
    }

    private function hasRecentDedupedLog(InvoiceFailed $event, int $userId): bool
    {
        if ($event->orderId === null) {
            return false;
        }
$since = Carbon::now()->subMinutes(60);

        return MailLog::query()
            ->where('key', 'invoice.failed')
            ->where('user_id', $userId)
            ->where('status', 'deduped')
            ->where('created_at', '>=', $since)
            ->where('metadata_json->order_id', $event->orderId)
            ->exists();
    }
}




