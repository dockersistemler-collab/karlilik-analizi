<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sabit Hizmet Bedeli Kademeleri
    |--------------------------------------------------------------------------
    |
    | Her satir: min <= satis_fiyati <= max araliginda uygulanacak sabit ucret.
    | max null ise ust sinir yoktur.
    |
    */
    'service_fee_brackets' => [
        ['min' => 0, 'max' => 199.99, 'fee' => 8.90],
        ['min' => 200, 'max' => 499.99, 'fee' => 13.90],
        ['min' => 500, 'max' => 999.99, 'fee' => 18.90],
        ['min' => 1000, 'max' => null, 'fee' => 24.90],
    ],

    /*
    |--------------------------------------------------------------------------
    | Stopaj Orani
    |--------------------------------------------------------------------------
    */
    'withholding_rate_percent' => 1.0,

    /*
    |--------------------------------------------------------------------------
    | Ek Hizmet Bedeli
    |--------------------------------------------------------------------------
    |
    | Her hesaplamada sabit TL olarak hizmet bedeline eklenir.
    |
    */
    'extra_service_fee_amount' => 0.0,

    /*
    |--------------------------------------------------------------------------
    | Pazaryeri Platform Hizmetleri
    |--------------------------------------------------------------------------
    |
    | Her hesaplamada ilgili pazaryeri icin sabit TL olarak hizmet bedeline eklenir.
    |
    */
    'platform_service_amount_trendyol' => 0.0,
    'platform_service_amount_hepsiburada' => 0.0,
    'platform_service_amount_n11' => 0.0,
    'platform_service_amount_amazon' => 0.0,
    'platform_service_amount_ciceksepeti' => 0.0,
];
