<?php

return [
    /*
     * Module pricing map (MVP).
     *
     * Keyed by module.code. Provide monthly/yearly prices in TRY.
     *
     * Example:
     * 'prices' => [
     *   'feature.reports' => ['monthly' => 199.90, 'yearly' => 1990.00],
     * ],
     */
    'prices' => [
        'feature.einvoice_api' => [
            'yearly' => 1990.00,
        ],
        'feature.einvoice_webhooks' => [
            'yearly' => 1490.00,
        ],
        'feature.cargo_tracking' => [
            'yearly' => 1490.00,
        ],
        'feature.bulk_cargo_label_print' => [
            'yearly' => 990.00,
        ],
        'feature.cargo_webhooks' => [
            'yearly' => 990.00,
        ],
        'integration.cargo.trendyol_express' => [
            'yearly' => 990.00,
        ],
        'integration.cargo.aras' => [
            'yearly' => 990.00,
        ],
        'integration.cargo.yurtici' => [
            'yearly' => 990.00,
        ],
    ],
];
