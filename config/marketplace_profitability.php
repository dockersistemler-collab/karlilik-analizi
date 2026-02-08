<?php

return [
    'sync' => [
        'orders_returns_range' => env('MP_SYNC_RANGE_ORDERS_RETURNS', 'last1day'),
        'fees_range' => env('MP_SYNC_RANGE_FEES', 'last30days'),
    ],
    'mart' => [
        'daily_time' => env('MP_MART_DAILY_TIME', '03:00'),
    ],
];
