<?php

namespace App\Services\Billing\Iyzico\Subscription;

use App\Services\Billing\Iyzico\IyzicoOptionsFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Iyzipay\DefaultHttpClient;
use Iyzipay\HashGenerator;
use Iyzipay\IyziAuthV2Generator;

class CardUpdateCheckoutFormService
{
    public function __construct(private readonly IyzicoOptionsFactory $optionsFactory)
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function initialize(
        string $callbackUrl,
        string $customerReferenceCode,
        ?string $subscriptionReferenceCode = null
    ): array {
        $options = $this->optionsFactory->make();
        $request = new CardUpdateCheckoutFormRequest();
        $request->setLocale('tr');
        $request->setConversationId((string) Str::uuid());
        $request->setCallbackUrl($callbackUrl);
        $request->setCustomerReferenceCode($customerReferenceCode);
        $request->setSubscriptionReferenceCode($subscriptionReferenceCode);

        $uri = '/v2/subscription/card-update/checkoutform/initialize';
        $url = rtrim($options->getBaseUrl(), '/').$uri;

        $rnd = uniqid();
        $auth = IyziAuthV2Generator::generateAuthContent($url, $options->getApiKey(), $options->getSecretKey(), $rnd, $request);
        $fallback = HashGenerator::generateHash($options->getApiKey(), $options->getSecretKey(), $rnd, $request);

        $headers = [
            'Accept: application/json',
            'Content-type: application/json',
            'Authorization: IYZWSv2 '.$auth,
            'x-iyzi-rnd: '.$rnd,
            'AUTHORIZATION_FALLBACK_HEADER: IYZWS '.$options->getApiKey().':'.$fallback,
            'x-iyzi-client-version: iyzipay-php-2.0.59',
        ];

        $client = DefaultHttpClient::create();
        $raw = $client->post($url, $headers, $request->toJsonString());

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            $decoded = [];
        }
$token = Arr::get($decoded, 'token')   Arr::get($decoded, 'checkoutFormToken');
        $html = Arr::get($decoded, 'checkoutFormContent')   Arr::get($decoded, 'htmlContent')   Arr::get($decoded, 'html');

        return [
            'raw' => $decoded,
            'status' => Arr::get($decoded, 'status'),
            'token' => $token,
            'checkoutFormContent' => $html,
            'errorCode' => Arr::get($decoded, 'errorCode'),
            'errorMessage' => Arr::get($decoded, 'errorMessage'),
            'errorGroup' => Arr::get($decoded, 'errorGroup'),
        ];
    }
}
