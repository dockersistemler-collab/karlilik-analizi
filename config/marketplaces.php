<?php

return [
    'use_mock_connectors' => (bool) env('MARKETPLACES_USE_MOCK_CONNECTORS', false),

    'trendyol' => [
        'base_url' => env('MARKETPLACES_TRENDYOL_BASE_URL', 'https://apigw.trendyol.com'),
        'timeout' => (int) env('MARKETPLACES_TRENDYOL_TIMEOUT', 20),
        'order_page_size' => (int) env('MARKETPLACES_TRENDYOL_ORDER_PAGE_SIZE', 200),
        'finance_page_size' => (int) env('MARKETPLACES_TRENDYOL_FINANCE_PAGE_SIZE', 500),
    ],
    'hepsiburada' => [
        'base_url' => env('MARKETPLACES_HEPSIBURADA_BASE_URL', 'https://api.hepsiburada.com'),
        'timeout' => (int) env('MARKETPLACES_HEPSIBURADA_TIMEOUT', 20),
        'page_size' => (int) env('MARKETPLACES_HEPSIBURADA_PAGE_SIZE', 200),
        'endpoints' => [
            'orders' => env('MARKETPLACES_HEPSIBURADA_ORDERS_ENDPOINT', '/orders/merchantid/{merchant_id}'),
            'returns' => env('MARKETPLACES_HEPSIBURADA_RETURNS_ENDPOINT', '/returns/merchantid/{merchant_id}'),
            'payouts' => env('MARKETPLACES_HEPSIBURADA_PAYOUTS_ENDPOINT', '/finance/merchantid/{merchant_id}/payouts'),
            'payout_transactions' => env('MARKETPLACES_HEPSIBURADA_PAYOUT_TRANSACTIONS_ENDPOINT', '/finance/merchantid/{merchant_id}/payouts/{payout_reference}/transactions'),
        ],
    ],
    'n11' => [
        'base_url' => env('MARKETPLACES_N11_BASE_URL', 'https://api.n11.com'),
        'timeout' => (int) env('MARKETPLACES_N11_TIMEOUT', 20),
        'page_size' => (int) env('MARKETPLACES_N11_PAGE_SIZE', 200),
        'endpoints' => [
            'orders' => env('MARKETPLACES_N11_ORDERS_ENDPOINT', '/order/list'),
            'returns' => env('MARKETPLACES_N11_RETURNS_ENDPOINT', '/return/list'),
            'payouts' => env('MARKETPLACES_N11_PAYOUTS_ENDPOINT', '/finance/payout/list'),
            'payout_transactions' => env('MARKETPLACES_N11_PAYOUT_TRANSACTIONS_ENDPOINT', '/finance/payout/{payout_reference}/transactions'),
        ],
    ],
    'amazon' => [
        'base_url' => env('MARKETPLACES_AMAZON_BASE_URL', 'https://sellingpartnerapi-eu.amazon.com'),
        'timeout' => (int) env('MARKETPLACES_AMAZON_TIMEOUT', 20),
        'page_size' => (int) env('MARKETPLACES_AMAZON_PAGE_SIZE', 100),
        'marketplace_id' => env('MARKETPLACES_AMAZON_MARKETPLACE_ID', 'A33AVAJ2PDY3EV'),
        'endpoints' => [
            'orders' => env('MARKETPLACES_AMAZON_ORDERS_ENDPOINT', '/orders/v0/orders'),
            'returns' => env('MARKETPLACES_AMAZON_RETURNS_ENDPOINT', '/orders/v0/returns'),
            'payouts' => env('MARKETPLACES_AMAZON_PAYOUTS_ENDPOINT', '/finances/v0/financialEventGroups'),
            'payout_transactions' => env('MARKETPLACES_AMAZON_PAYOUT_TRANSACTIONS_ENDPOINT', '/finances/v0/financialEventGroups/{payout_reference}/events'),
        ],
    ],
];
