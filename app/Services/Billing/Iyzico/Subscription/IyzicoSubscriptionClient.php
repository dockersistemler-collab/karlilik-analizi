<?php

namespace App\Services\Billing\Iyzico\Subscription;

use App\Services\Billing\Iyzico\IyzicoOptionsFactory;
use Illuminate\Support\Str;
use Iyzipay\Model\Subscription\SubscriptionPricingPlan;
use Iyzipay\Model\Subscription\SubscriptionProduct;
use Iyzipay\Model\Subscription\SubscriptionCancel;
use Iyzipay\Model\Subscription\SubscriptionCreateCheckoutForm;
use Iyzipay\Model\Subscription\SubscriptionDetails;
use Iyzipay\Model\Subscription\SubscriptionUpgrade;
use Iyzipay\Request\Subscription\SubscriptionCancelRequest;
use Iyzipay\Request\Subscription\SubscriptionCreatePricingPlanRequest;
use Iyzipay\Request\Subscription\SubscriptionCreateProductRequest;
use Iyzipay\Request\Subscription\SubscriptionCreateCheckoutFormRequest;
use Iyzipay\Request\Subscription\SubscriptionDetailsRequest;
use Iyzipay\Request\Subscription\SubscriptionUpgradeRequest;

class IyzicoSubscriptionClient
{
    public function __construct(private readonly IyzicoOptionsFactory $optionsFactory)
    {
    }

    public function createSubscriptionCheckoutForm(array $payload): SubscriptionCreateCheckoutForm
    {
        $request = new SubscriptionCreateCheckoutFormRequest();
        $request->setCallbackUrl($payload['callbackUrl']);
        $request->setPricingPlanReferenceCode($payload['pricingPlanReferenceCode']);
        $request->setSubscriptionInitialStatus($payload['initialStatus'] ?? 'PENDING');
        $request->setCustomer($payload['customer']);
        $request->setBuyer($payload['buyer']);
        $request->setShippingAddress($payload['shippingAddress']);
        $request->setBillingAddress($payload['billingAddress']);
        $request->setPaymentCard($payload['paymentCard'] ?? null);

        return SubscriptionCreateCheckoutForm::create($request, $this->optionsFactory->make());
    }

    public function createProduct(string $name, ?string $description = null): string
    {
        $request = new SubscriptionCreateProductRequest();
        $request->setLocale('tr');
        $request->setConversationId((string) Str::uuid());
        $request->setName($name);
        if ($description) {
            $request->setDescription($description);
        }
$result = SubscriptionProduct::create($request, $this->optionsFactory->make());

        return (string) $result->getReferenceCode();
    }

    public function createPricingPlan(
        string $productReferenceCode,
        string $planName,
        float $price,
        string $currency = 'TRY',
        string $interval = 'MONTHLY',
        int $intervalCount = 1
    ): string {
        $request = new SubscriptionCreatePricingPlanRequest();
        $request->setLocale('tr');
        $request->setConversationId((string) Str::uuid());
        $request->setProductReferenceCode($productReferenceCode);
        $request->setName($planName);
        $request->setPrice(number_format($price, 2, '.', ''));
        $request->setCurrencyCode($currency);
        $request->setPaymentInterval($interval);
        $request->setPaymentIntervalCount($intervalCount);
        $request->setPlanPaymentType('RECURRING');

        $result = SubscriptionPricingPlan::create($request, $this->optionsFactory->make());

        return (string) $result->getReferenceCode();
    }

    public function retrieveSubscription(string $subscriptionReferenceCode): SubscriptionDetails
    {
        $request = new SubscriptionDetailsRequest();
        $request->setSubscriptionReferenceCode($subscriptionReferenceCode);

        return SubscriptionDetails::retrieve($request, $this->optionsFactory->make());
    }

    public function cancelSubscription(string $subscriptionReferenceCode): SubscriptionCancel
    {
        $request = new SubscriptionCancelRequest();
        $request->setSubscriptionReferenceCode($subscriptionReferenceCode);

        return SubscriptionCancel::cancel($request, $this->optionsFactory->make());
    }

    public function upgradeSubscription(string $subscriptionReferenceCode, string $pricingPlanReferenceCode): SubscriptionUpgrade
    {
        $request = new SubscriptionUpgradeRequest();
        $request->setSubscriptionReferenceCode($subscriptionReferenceCode);
        $request->setNewPricingPlanReferenceCode($pricingPlanReferenceCode);

        return SubscriptionUpgrade::update($request, $this->optionsFactory->make());
    }
}
