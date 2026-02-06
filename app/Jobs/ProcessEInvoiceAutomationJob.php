<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\EInvoices\EInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessEInvoiceAutomationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $orderId)
    {
    }

    public function handle(EInvoiceService $einvoices): void
    {
        $order = Order::query()
            ->with(['user', 'marketplace'])
            ->find($this->orderId);

        if (!$order) {
            return;
        }

        try {
            $einvoices->maybeCreateDraftFromOrder($order);
            $einvoices->maybeIssueFromOrder($order);
        } catch (Throwable $e) {
            Log::error('einvoices.automation.failed', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

