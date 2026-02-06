<?php

return [
    'reprocess' => [
        'idempotency_window_seconds' => 120,
        'webhook_allowlist' => [
            'iyzico.webhook.*',
        ],
        'job_allowlist' => [
            'dunning.*',
            'invoice.*',
        ],
    ],
];
