<?php

namespace App\Services\Billing\Iyzico;

use Iyzipay\Model\CheckoutForm;
use Iyzipay\Request\RetrieveCheckoutFormRequest;
use Iyzipay\Model\Locale;

class CheckoutStatusService
{
    public function __construct(private readonly IyzicoOptionsFactory $optionsFactory)
    {
    }

    public function retrieve(string $token): CheckoutForm
    {
        $request = new RetrieveCheckoutFormRequest();
        $request->setLocale(Locale::TR);
        $request->setToken($token);

        return CheckoutForm::retrieve($request, $this->optionsFactory->make());
    }
}
