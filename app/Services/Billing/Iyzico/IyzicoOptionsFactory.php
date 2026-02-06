<?php

namespace App\Services\Billing\Iyzico;

use App\Services\SystemSettings\SettingsRepository;
use Iyzipay\Options;

class IyzicoOptionsFactory
{
    public function __construct(private readonly SettingsRepository $settings)
    {
    }

    public function make(): Options
    {
        $options = new Options();

        $apiKey = (string) $this->settings->get('billing', 'iyzico.api_key', '');
        $secretKey = (string) $this->settings->get('billing', 'iyzico.secret_key', '');
        $sandbox = filter_var($this->settings->get('billing', 'iyzico.sandbox', true), FILTER_VALIDATE_BOOLEAN);

        $options->setApiKey($apiKey);
        $options->setSecretKey($secretKey);
        $options->setBaseUrl($sandbox ? 'https://sandbox-api.iyzipay.com' : 'https://api.iyzipay.com');

        return $options;
    }
}
