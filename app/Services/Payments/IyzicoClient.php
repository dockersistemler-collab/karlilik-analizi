<?php

namespace App\Services\Payments;

use Illuminate\Support\Arr;
use Iyzipay\Model\CheckoutForm;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Options;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Iyzipay\Request\RetrieveCheckoutFormRequest;

class IyzicoClient
{
    /**
     * @param array<string,mixed> $payload Iyzipay request fields (SDK expects setter keys).
     * @return array<string,mixed> Normalized response array.
     */
    public function initializeCheckoutForm(array $payload): array
    {
        $request = new CreateCheckoutFormInitializeRequest();

        foreach ($payload as $key => $value) {
            $method = 'set'.ucfirst($key);
            if (method_exists($request, $method)) {
                $request->{$method}($value);
            }
        }
        $response = CheckoutFormInitialize::create($request, $this->options());

        return [
            'raw' => $this->toArray($response),
            'status' => $this->normalizeString($response->getStatus()),
            'token' => $response->getToken(),
            'checkoutFormContent' => $response->getCheckoutFormContent(),
            'errorCode' => $this->normalizeString($response->getErrorCode()),
            'errorMessage' => $this->normalizeString($response->getErrorMessage()),
            'errorGroup' => $this->normalizeString($response->getErrorGroup()),
        ];
    }

    /**
     * @return array<string,mixed> Normalized response array.
     */
    public function retrieveCheckoutForm(string $token, ?string $conversationId = null): array
    {
        $request = new RetrieveCheckoutFormRequest();
        $request->setToken($token);
        $request->setConversationId($conversationId ?? "retrieve:{$token}");

        $response = CheckoutForm::retrieve($request, $this->options());

        $paymentConversationId = null;
        if (method_exists($response, 'getPaymentConversationId')) {
            $paymentConversationId = $response->getPaymentConversationId();
        }

        return [
            'raw' => $this->toArray($response),
            'status' => $this->normalizeString($response->getStatus()),
            'conversationId' => $this->normalizeString($response->getConversationId()),
            'paymentId' => $this->normalizeString($response->getPaymentId()),
            'paymentConversationId' => $this->normalizeString($paymentConversationId),
            'paidPrice' => $response->getPaidPrice(),
            'price' => $response->getPrice(),
            'currency' => $this->normalizeString($response->getCurrency()),
            'basketId' => $this->normalizeString($response->getBasketId()),
            'errorCode' => $this->normalizeString($response->getErrorCode()),
            'errorMessage' => $this->normalizeString($response->getErrorMessage()),
            'errorGroup' => $this->normalizeString($response->getErrorGroup()),
        ];
    }

    private function options(): Options
    {
        $options = new Options();
        $options->setApiKey((string) config('services.iyzico.api_key'));
        $options->setSecretKey((string) config('services.iyzico.secret_key'));
        $options->setBaseUrl((string) config('services.iyzico.base_url'));
        return $options;
    }

    /**
     * The Iyzipay SDK models don't implement Arrayable/JsonSerializable consistently.
     * This is a best-effort normalization for storage/logging.
     *
     * @return array<string,mixed>
     */
    private function toArray(object $response): array
    {
        $data = get_object_vars($response);
        if (!is_array($data) || empty($data)) {
            return [];
        }

        return Arr::map($data, function ($value) {
            if (is_object($value)) {
                return get_object_vars($value);
            }
            return $value;
        });
    }

    private function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (!is_string($value)) {
            return null;
        }
$value = trim($value);
        return $value === '' ? null : $value;
    }
}
