<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Models\MailLog;
use App\Models\User;
use App\Services\Mail\MailSender;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendInvoiceCreatedMail implements ShouldQueue
{
    public function __construct(private readonly MailSender $sender)
    {
    }

    public function handle(InvoiceCreated $event): void
    {
        $user = User::query()->find($event->userId);
        if (!$user) {
            return;
        }

        if ($this->isDeduped($event, $user->id)) {
            if (!$this->hasDedupedLog($event, $user->id)) {
                MailLog::create([
                    'key' => 'invoice.created',
                    'user_id' => $user->id,
                    'status' => 'deduped',
                    'provider_message_id' => null,
                    'error' => null,
                    'metadata_json' => [
                        'invoice_id' => $event->invoiceId,
                        'order_id' => $event->orderId,
                        'marketplace' => $event->marketplace,
                        'occurred_at' => $event->occurredAt,
                        'reason' => 'dedupe_invoice_id',
                    ],
                    'sent_at' => null,
                ]);
            }
            return;
        }
$this->sender->send('invoice.created', $user, [
            'user_name' => $user->name,
            'invoice_number' => $event->invoiceNumber,
            'invoice_url' => $event->invoiceUrl,
            'total_amount' => $event->totalAmount,
            'currency' => $event->currency,
            'marketplace' => $event->marketplace,
            'order_id' => $event->orderId,
        ], [
            'invoice_id' => $event->invoiceId,
            'order_id' => $event->orderId,
            'marketplace' => $event->marketplace,
            'occurred_at' => $event->occurredAt,
        ]);
    }

    private function isDeduped(InvoiceCreated $event, int $userId): bool
    {
        return MailLog::query()
            ->where('key', 'invoice.created')
            ->where('user_id', $userId)
            ->where('metadata_json->invoice_id', $event->invoiceId)
            ->exists();
    }

    private function hasDedupedLog(InvoiceCreated $event, int $userId): bool
    {
        return MailLog::query()
            ->where('key', 'invoice.created')
            ->where('user_id', $userId)
            ->where('status', 'deduped')
            ->where('metadata_json->invoice_id', $event->invoiceId)
            ->exists();
    }
}
