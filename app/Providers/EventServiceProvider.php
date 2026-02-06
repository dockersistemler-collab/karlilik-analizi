<?php

namespace App\Providers;

use App\Events\OrderStatusChanged;
use App\Listeners\DispatchEInvoiceAutomation;
use App\Domain\Tickets\Events\TicketAssigned;
use App\Domain\Tickets\Events\TicketCreated;
use App\Domain\Tickets\Events\TicketReplied;
use App\Domain\Tickets\Events\TicketStatusChanged;
use App\Domain\Tickets\Listeners\SendTicketNotification;
use App\Domain\Tickets\Listeners\WriteSystemMessage;
use App\Events\SupportViewStarted;
use App\Events\MarketplaceConnectionLost;
use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Events\MarketplaceTokenExpiring;
use App\Events\QuotaWarningReached;
use App\Events\QuotaExceeded;
use App\Events\InvoiceCreated;
use App\Events\InvoiceFailed;
use App\Events\SubscriptionStarted;
use App\Events\SubscriptionRenewed;
use App\Events\SubscriptionCancelled;
use App\Events\TrialEnded;
use App\Listeners\SendSupportViewStartedMail;
use App\Listeners\SendMarketplaceConnectionLostMail;
use App\Listeners\SendPaymentFailedMail;
use App\Listeners\SendPaymentSucceededMail;
use App\Listeners\SendMarketplaceTokenExpiringMail;
use App\Listeners\SendQuotaWarningMail;
use App\Listeners\SendQuotaExceededMail;
use App\Listeners\SendInvoiceCreatedMail;
use App\Listeners\SendInvoiceFailedMail;
use App\Listeners\SendSubscriptionStartedMail;
use App\Listeners\SendSubscriptionRenewedMail;
use App\Listeners\SendSubscriptionCancelledMail;
use App\Listeners\SendTrialEndedMail;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderStatusChanged::class => [
            DispatchEInvoiceAutomation::class,
        ],
        TicketCreated::class => [
            SendTicketNotification::class,
        ],
        TicketReplied::class => [
            SendTicketNotification::class,
        ],
        TicketAssigned::class => [
            SendTicketNotification::class,
        ],
        TicketStatusChanged::class => [
            WriteSystemMessage::class,
        ],
        SupportViewStarted::class => [
            SendSupportViewStartedMail::class,
        ],
        MarketplaceConnectionLost::class => [
            SendMarketplaceConnectionLostMail::class,
        ],
        PaymentFailed::class => [
            SendPaymentFailedMail::class,
        ],
        PaymentSucceeded::class => [
            SendPaymentSucceededMail::class,
        ],
        MarketplaceTokenExpiring::class => [
            SendMarketplaceTokenExpiringMail::class,
        ],
        QuotaWarningReached::class => [
            SendQuotaWarningMail::class,
        ],
        QuotaExceeded::class => [
            SendQuotaExceededMail::class,
        ],
        InvoiceCreated::class => [
            SendInvoiceCreatedMail::class,
        ],
        InvoiceFailed::class => [
            SendInvoiceFailedMail::class,
        ],
        SubscriptionStarted::class => [
            SendSubscriptionStartedMail::class,
        ],
        SubscriptionRenewed::class => [
            SendSubscriptionRenewedMail::class,
        ],
        SubscriptionCancelled::class => [
            SendSubscriptionCancelledMail::class,
        ],
        TrialEnded::class => [
            SendTrialEndedMail::class,
        ],
    ];
}
