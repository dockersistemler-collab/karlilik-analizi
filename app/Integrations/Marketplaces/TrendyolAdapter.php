<?php

namespace App\Integrations\Marketplaces;

use App\Integrations\Marketplaces\DTO\AdjustmentDTO;
use App\Integrations\Marketplaces\DTO\CoreOrderItemDTO;
use App\Integrations\Marketplaces\Support\DateRange;
use App\Models\MarketplaceAccount;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class TrendyolAdapter extends BaseMarketplaceAdapter
{
    protected function defaultHeaders(MarketplaceAccount $account): array
    {
        $creds = is_array($account->credentials) ? $account->credentials : [];

        return [
            'User-Agent' => (string) Arr::get($creds, 'user_agent', 'Pazar/MarketplaceAdapter'),
            'Accept' => 'application/json',
        ];
    }

    public function fetchOrders(MarketplaceAccount $account, DateRange $range): iterable
    {
        $baseUrl = $this->resolveBaseUrl($account);
        $supplierId = (string) Arr::get($account->credentials ?? [], 'supplier_id', '');
        $apiKey = (string) Arr::get($account->credentials ?? [], 'api_key', '');
        $apiSecret = (string) Arr::get($account->credentials ?? [], 'api_secret', '');

        if ($baseUrl === '' || $supplierId === '' || $apiKey === '' || $apiSecret === '') {
            Log::warning('Trendyol orders fetch skipped: missing credentials/base_url', [
                'tenant_id' => $account->tenant_id,
                'account_id' => $account->id,
            ]);
            return [];
        }

        $url = rtrim($baseUrl, '/') . '/' . $supplierId . '/orders';
        $query = [
            // TODO: Trendyol order API tarih filtreleri: ms epoch (startDate/endDate).
            'startDate' => $range->from->timestamp * 1000,
            'endDate' => $range->to->timestamp * 1000,
        ];

        $response = $this->httpClient($account)
            ->withBasicAuth($apiKey, $apiSecret)
            ->get($url, $query);

        if (!$response->successful()) {
            Log::warning('Trendyol orders fetch failed', [
                'tenant_id' => $account->tenant_id,
                'account_id' => $account->id,
                'status' => $response->status(),
            ]);
            return [];
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            return [];
        }

        // TODO: Trendyol response yapısına göre normalize edilecek.
        return $payload['content'] ?? $payload['items'] ?? $payload;
    }

    public function fetchReturns(MarketplaceAccount $account, DateRange $range): iterable
    {
        $baseUrl = $this->resolveBaseUrl($account);
        $supplierId = (string) Arr::get($account->credentials ?? [], 'supplier_id', '');
        $apiKey = (string) Arr::get($account->credentials ?? [], 'api_key', '');
        $apiSecret = (string) Arr::get($account->credentials ?? [], 'api_secret', '');

        if ($baseUrl === '' || $supplierId === '' || $apiKey === '' || $apiSecret === '') {
            return [];
        }

        $url = rtrim($baseUrl, '/') . '/' . $supplierId . '/returns';
        $query = [
            // TODO: Trendyol return API tarih filtreleri: ms epoch (startDate/endDate).
            'startDate' => $range->from->timestamp * 1000,
            'endDate' => $range->to->timestamp * 1000,
        ];

        $response = $this->httpClient($account)
            ->withBasicAuth($apiKey, $apiSecret)
            ->get($url, $query);

        if (!$response->successful()) {
            Log::warning('Trendyol returns fetch failed', [
                'tenant_id' => $account->tenant_id,
                'account_id' => $account->id,
                'status' => $response->status(),
            ]);
            return [];
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            return [];
        }

        return $payload['content'] ?? $payload['items'] ?? $payload;
    }

    public function fetchFees(MarketplaceAccount $account, DateRange $range): iterable
    {
        $baseUrl = $this->resolveBaseUrl($account);
        $supplierId = (string) Arr::get($account->credentials ?? [], 'supplier_id', '');
        $apiKey = (string) Arr::get($account->credentials ?? [], 'api_key', '');
        $apiSecret = (string) Arr::get($account->credentials ?? [], 'api_secret', '');

        if ($baseUrl === '' || $supplierId === '' || $apiKey === '' || $apiSecret === '') {
            return [];
        }

        $url = rtrim($baseUrl, '/') . '/' . $supplierId . '/settlements';
        $query = [
            // TODO: Trendyol settlement/fees API tarih filtreleri: ms epoch (startDate/endDate).
            'startDate' => $range->from->timestamp * 1000,
            'endDate' => $range->to->timestamp * 1000,
        ];

        $response = $this->httpClient($account)
            ->withBasicAuth($apiKey, $apiSecret)
            ->get($url, $query);

        if (!$response->successful()) {
            Log::warning('Trendyol fees fetch failed', [
                'tenant_id' => $account->tenant_id,
                'account_id' => $account->id,
                'status' => $response->status(),
            ]);
            return [];
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            return [];
        }

        return $payload['content'] ?? $payload['items'] ?? $payload;
    }

    public function mapOrderItemToCore(array $raw): CoreOrderItemDTO
    {
        $orderDate = $this->parseDate($raw['order_date'] ?? null) ?? CarbonImmutable::now();

        return new CoreOrderItemDTO(
            marketplace: 'trendyol',
            orderId: (string) ($raw['order_id'] ?? $raw['orderId'] ?? 'unknown'),
            orderItemId: (string) ($raw['order_item_id'] ?? $raw['orderItemId'] ?? 'unknown'),
            orderDate: $orderDate,
            shipDate: $this->parseDate($raw['ship_date'] ?? null),
            deliveredDate: $this->parseDate($raw['delivered_date'] ?? null),
            sku: $raw['sku'] ?? null,
            productId: isset($raw['product_id']) ? (int) $raw['product_id'] : null,
            variant: $raw['variant'] ?? null,
            quantity: (int) ($raw['quantity'] ?? 1),
            currency: (string) ($raw['currency'] ?? 'TRY'),
            fxRate: (float) ($raw['fx_rate'] ?? 1),
            grossSales: (float) ($raw['gross_sales'] ?? 0),
            discounts: (float) ($raw['discounts'] ?? 0),
            refunds: (float) ($raw['refunds'] ?? 0),
            commissionFee: (float) ($raw['commission_fee'] ?? 0),
            paymentFee: (float) ($raw['payment_fee'] ?? 0),
            shippingFee: (float) ($raw['shipping_fee'] ?? 0),
            otherFees: (float) ($raw['other_fees'] ?? 0),
            vatAmount: isset($raw['vat_amount']) ? (float) $raw['vat_amount'] : null,
            taxAmount: isset($raw['tax_amount']) ? (float) $raw['tax_amount'] : null,
            cogsUnit: isset($raw['cogs_unit']) ? (float) $raw['cogs_unit'] : null,
            status: (string) ($raw['status'] ?? 'paid')
        );
    }

    public function mapReturnToCoreAdjustments(array $raw): AdjustmentDTO
    {
        $occurredAt = $this->parseDate($raw['occurred_at'] ?? null) ?? CarbonImmutable::now();
        return new AdjustmentDTO(
            marketplace: 'trendyol',
            orderItemId: (string) ($raw['order_item_id'] ?? $raw['orderItemId'] ?? 'unknown'),
            type: 'refund',
            amount: (float) ($raw['amount'] ?? 0),
            currency: (string) ($raw['currency'] ?? 'TRY'),
            occurredAt: $occurredAt
        );
    }

    public function mapFeeToCoreAdjustments(array $raw): AdjustmentDTO
    {
        $occurredAt = $this->parseDate($raw['occurred_at'] ?? null) ?? CarbonImmutable::now();
        return new AdjustmentDTO(
            marketplace: 'trendyol',
            orderItemId: (string) ($raw['order_item_id'] ?? $raw['orderItemId'] ?? 'unknown'),
            type: (string) ($raw['fee_type'] ?? 'fee'),
            amount: (float) ($raw['amount'] ?? 0),
            currency: (string) ($raw['currency'] ?? 'TRY'),
            occurredAt: $occurredAt
        );
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (is_string($value) && trim($value) !== '') {
            return CarbonImmutable::parse($value);
        }
        return null;
    }

    private function resolveBaseUrl(MarketplaceAccount $account): string
    {
        $creds = is_array($account->credentials) ? $account->credentials : [];
        $baseUrl = (string) Arr::get($creds, 'base_url', '');
        if ($baseUrl !== '') {
            return $baseUrl;
        }

        $isTest = (bool) Arr::get($creds, 'is_test', false);
        return $isTest
            ? 'https://stageapi.trendyol.com/stage/sapigw/suppliers'
            : 'https://api.trendyol.com/sapigw/suppliers';
    }
}
