<?php

namespace App\Services\Billing\Iyzico;

use App\Models\BillingCheckout;
use App\Models\User;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Model\Locale;
use Iyzipay\Model\PaymentGroup;
use Iyzipay\Model\Currency;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;

class CheckoutFormService
{
    public function __construct(private readonly IyzicoOptionsFactory $optionsFactory)
    {
    }

    public function initializeCheckout(BillingCheckout $checkout, User $user, User $tenant, array $plan): CheckoutInitResult
    {
        $price = (string) number_format((float) ($plan['price_monthly'] ?? 0), 2, '.', '');

        $request = new CreateCheckoutFormInitializeRequest();
        $request->setLocale(Locale::TR);
        $request->setConversationId($checkout->id);
        $request->setPrice($price);
        $request->setPaidPrice($price);
        $request->setCurrency(Currency::TL);
        $request->setBasketId($checkout->id);
        $request->setPaymentGroup(PaymentGroup::SUBSCRIPTION);
        $request->setCallbackUrl(route('billing.iyzico.callback'));

        $buyer = new Buyer();
        $buyer->setId((string) $tenant->id);
        $buyer->setName($tenant->name ?: 'Musteri');
        $buyer->setSurname(' ');
        $buyer->setGsmNumber('+905555555555');
        $buyer->setEmail($tenant->email);
        $buyer->setIdentityNumber('11111111111');
        $buyer->setRegistrationAddress('Istanbul');
        $buyer->setIp('127.0.0.1');
        $buyer->setCity('Istanbul');
        $buyer->setCountry('Turkey');
        $buyer->setZipCode('34000');
        $request->setBuyer($buyer);

        $request->setBillingAddress($this->makeAddress('Fatura', $tenant));
        $request->setShippingAddress($this->makeAddress('Teslimat', $tenant));

        $item = new BasketItem();
        $item->setId((string) ($plan['name'] ?? $checkout->plan_code));
        $item->setName((string) ($plan['name'] ?? $checkout->plan_code));
        $item->setCategory1('SaaS');
        $item->setItemType(BasketItemType::VIRTUAL);
        $item->setPrice($price);
        $request->setBasketItems([$item]);

        $initialize = CheckoutFormInitialize::create($request, $this->optionsFactory->make());

        return new CheckoutInitResult(
            (string) $initialize->getToken(),
            (string) $initialize->getCheckoutFormContent()
        );
    }

    private function makeAddress(string $label, User $tenant): \Iyzipay\Model\Address
    {
        $address = new \Iyzipay\Model\Address();
        $address->setContactName($label);
        $address->setCity('Istanbul');
        $address->setCountry('Turkey');
        $address->setAddress($tenant->billing_address ?: $tenant->company_address ?: 'Istanbul');
        $address->setZipCode('34000');

        return $address;
    }
}
