<?php

namespace App\Enums;

enum NotificationSource: string
{
    case OrderSync = 'order_sync';
    case StockSync = 'stock_sync';
    case Invoice = 'invoice';
    case Auth = 'auth';
    case Webhook = 'webhook';
    case System = 'system';
}