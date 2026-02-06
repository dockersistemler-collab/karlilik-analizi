<?php

return [
    /*
     * Available cargo provider keys.
     *
     * Licensing:
     *   - feature.cargo_tracking
     *   - integration.cargo.{provider_key}
     */
    'providers' => [
        'trendyol_express' => [
            'label' => 'Trendyol Express',
            'requires_installation' => true,
            'credentials' => [
                'api_key' => ['type' => 'text', 'label' => 'API Key', 'required' => true],
                'api_secret' => ['type' => 'text', 'label' => 'API Secret', 'required' => true],
            ],
        ],
        'aras' => [
            'label' => 'Aras Kargo',
            'requires_installation' => true,
            'credentials' => [
                'username' => ['type' => 'text', 'label' => 'Kullanıcı Adı', 'required' => true],
                'password' => ['type' => 'password', 'label' => 'Şifre', 'required' => true],
            ],
        ],
        'yurtici' => [
            'label' => 'Yurtiçi Kargo',
            'requires_installation' => true,
            'credentials' => [
                'username' => ['type' => 'text', 'label' => 'Kullanıcı Adı', 'required' => true],
                'password' => ['type' => 'password', 'label' => 'Şifre', 'required' => true],
            ],
        ],
    ],
];
