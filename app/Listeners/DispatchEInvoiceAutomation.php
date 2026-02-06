<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Jobs\ProcessEInvoiceAutomationJob;

class DispatchEInvoiceAutomation
{
    public function handle(OrderStatusChanged $event): void
    {
        ProcessEInvoiceAutomationJob::dispatch($event->orderId);
    }
}

