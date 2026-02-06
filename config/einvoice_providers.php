<?php

return [
    /*
     * Available provider keys in the system.
     *
     * Licensing is handled via module entitlements:
     *   - feature.einvoice
     *   - integration.einvoice.{provider_key}
     */
    'providers' => [
        'null' => [
            'label' => 'Yerel (Null)',
            'requires_installation' => false,
        ],
        'custom' => [
            'label' => 'Custom HTTP (Push)',
            'requires_installation' => true,
            'credentials' => [
                'base_url' => ['type' => 'url', 'label' => 'Base URL'],
                'api_key' => ['type' => 'text', 'label' => 'API Key'],
            ],
        ],
        // 'foriba' => [...],
        // 'logo' => [...],
    ],
];

