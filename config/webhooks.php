<?php

return [
    /*
     * Security
     */
    'allow_insecure_http' => (bool) env('WEBHOOKS_ALLOW_INSECURE_HTTP', false),

    /*
     * Logging
     */
    'log_payload' => (bool) env('WEBHOOKS_LOG_PAYLOAD', true),
    'log_pii' => (bool) env('WEBHOOKS_LOG_PII', false),

    /*
     * Signature replay protection guidance (clients should enforce)
     */
    'timestamp_tolerance_seconds' => (int) env('WEBHOOKS_TIMESTAMP_TOLERANCE_SECONDS', 300),

    /*
     * HTTP
     */
    'connect_timeout_seconds' => (int) env('WEBHOOKS_CONNECT_TIMEOUT_SECONDS', 5),
    'timeout_seconds' => (int) env('WEBHOOKS_TIMEOUT_SECONDS', 15),
];

