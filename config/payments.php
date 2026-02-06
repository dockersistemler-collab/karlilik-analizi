<?php

return [
    /*
     * Payment mode:
     * - iyzico: use real Iyzipay/Iyzico Checkout Form
     * - fake: mark purchases as paid immediately (dev)
     */
    'mode' => env('PAYMENTS_MODE', 'iyzico'), // fake|iyzico

    'iyzico_enabled' => (bool) (env('IYZICO_API_KEY') && env('IYZICO_SECRET_KEY') && env('IYZICO_BASE_URL')),
];

