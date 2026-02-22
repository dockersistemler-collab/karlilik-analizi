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
    ],
    'n11' => [
        'base_url' => env('MARKETPLACES_N11_BASE_URL', 'https://api.n11.com'),
        'timeout' => (int) env('MARKETPLACES_N11_TIMEOUT', 20),
    ],
    'amazon' => [
        'base_url' => env('MARKETPLACES_AMAZON_BASE_URL', 'https://sellingpartnerapi-eu.amazon.com'),
        'timeout' => (int) env('MARKETPLACES_AMAZON_TIMEOUT', 20),
    ],
];
