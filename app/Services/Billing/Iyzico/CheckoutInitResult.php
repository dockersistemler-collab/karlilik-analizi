<?php

namespace App\Services\Billing\Iyzico;

class CheckoutInitResult
{
    public function __construct(
        public readonly string $token,
        public readonly string $checkoutFormContent
    ) {
    }
}
