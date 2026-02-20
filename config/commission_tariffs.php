<?php

return [
    // Platform hizmet bedeli sabit (TL).
    'platform_service_fee' => 9.90,

    // KDV oranlari (%).
    'shipping_vat_rate' => 20,
    'service_vat_rate' => 20,
    'commission_vat_rate' => 20,

    // Kargo ucreti hesaplama modu: desi_based | existing
    'fallback_shipping_fee_mode' => 'desi_based',

    // Desi bazli kargo ucreti tablosu (desi => ucret).
    'desi_fee_table' => [
        1 => 49.90,
        2 => 59.90,
        3 => 69.90,
        5 => 89.90,
        10 => 129.90,
    ],
];
