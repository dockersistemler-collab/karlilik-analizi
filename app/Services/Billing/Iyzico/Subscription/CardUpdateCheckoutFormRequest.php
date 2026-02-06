<?php

namespace App\Services\Billing\Iyzico\Subscription;

use Iyzipay\JsonBuilder;
use Iyzipay\Request;

class CardUpdateCheckoutFormRequest extends Request
{
    private ?string $callbackUrl = null;
    private ?string $customerReferenceCode = null;
    private ?string $subscriptionReferenceCode = null;

    public function setCallbackUrl(string $callbackUrl): void
    {
        $this->callbackUrl = $callbackUrl;
    }

    public function setCustomerReferenceCode(string $customerReferenceCode): void
    {
        $this->customerReferenceCode = $customerReferenceCode;
    }

    public function setSubscriptionReferenceCode(?string $subscriptionReferenceCode): void
    {
        $this->subscriptionReferenceCode = $subscriptionReferenceCode ?: null;
    }

    public function getJsonObject()
    {
        return JsonBuilder::create()
            ->add('locale', $this->getLocale())
            ->add('conversationId', $this->getConversationId())
            ->add('callbackUrl', $this->callbackUrl)
            ->add('customerReferenceCode', $this->customerReferenceCode)
            ->add('subscriptionReferenceCode', $this->subscriptionReferenceCode)
            ->getObject();
    }
}
