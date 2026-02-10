<?php

namespace App\Integrations\Marketplaces;

use App\Integrations\Marketplaces\DTO\AdjustmentDTO;
use App\Integrations\Marketplaces\DTO\CoreOrderItemDTO;
use App\Integrations\Marketplaces\Support\DateRange;
use App\Models\MarketplaceAccount;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class AmazonAdapter extends BaseMarketplaceAdapter
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
        if ($baseUrl === '' || !$this->hasAuth($account)) {
            Log::warning('Amazon orders fetch skipped: missing credentials/base_url', [
                'tenant_id' => $account->tenant_id,
                'account_id' => $account->id,
            ]);
            return [];
        }

        $url = rtrim($baseUrl, '/') . '/orders';
        $query = [
            // TODO: Amazon Orders API tarih filtreleri uyarlanacak.
            'createdAfter' => $range->from->toIso8601String(),
            'createdBefore' => $range->to->toIso8601String(),
        ];

        $response = $this->authRequest($account)->get($url, $query);
        if (!$response->successful()) {
            Log::warning('Amazon orders fetch failed', [
                'tenant_id' => $account->tenant_id,
                'account_id' => $account->id,
                'status' => $response->status(),
            ]);
            return [];
        }

        $payload = $response->json();
        return is_array($payload) ? ($payload['orders'] ?? $payload['items'] ?? $payload) : [];
    }

    public function fetchReturns(MarketplaceAccount $account, DateRange $range): iterable
    {
        $baseUrl = $this->resolveBaseUrl($account);
        if ($baseUrl === '' || !$this->hasAuth($account)) {
            return [];
        }

        $url = rtrim($baseUrl, '/') . '/returns';
        $query = [
            'createdAfter' => $range->from->toIso8601String(),
            'createdBefore' => $range->to->toIso8601String(),
        ];

        $response = $this->authRequest($account)->get($url, $query);
        if (!$response->successful()) {
            Log::warning('Amazon returns fetch failed', [
                'tenant_id' => $account->tenant_id,
                'account_id' => $account->id,
                'status' => $response->status(),
            ]);
            return [];
        }

        $payload = $response->json();
        return is_array($payload) ? ($payload['returns'] ?? $payload['items'] ?? $payload) : [];
    }

    public function fetchFees(MarketplaceAccount $account, DateRange $range): iterable
    {
        $baseUrl = $this->resolveBaseUrl($account);
        if ($baseUrl === '' || !$this->hasAuth($account)) {
            return [];
        }

        $url = rtrim($baseUrl, '/') . '/fees';
        $query = [
            'startDate' => $range->from->toIso8601String(),
            'endDate' => $range->to->toIso8601String(),
        ];

        $response = $this->authRequest($account)->get($url, $query);
        if (!$response->successful()) {
            Log::warning('Amazon fees fetch failed', [
                'tenant_id' => $account->tenant_id,
                'account_id' => $account->id,
                'status' => $response->status(),
            ]);
            return [];
        }

        $payload = $response->json();
        return is_array($payload) ? ($payload['fees'] ?? $payload['items'] ?? $payload) : [];
    }

    public function mapOrderItemToCore(array $raw): CoreOrderItemDTO
    {
        $orderDate = $this->parseDate($raw['order_date'] ?? null) ?? CarbonImmutable::now();

        return new CoreOrderItemDTO(
            marketplace: 'amazon',
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
            marketplace: 'amazon',
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
            marketplace: 'amazon',
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
        return $this->allowlistedBaseUrl($account, $baseUrl, [
            'amazon.com',
            'amazon.com.tr',
            'amazon.co.uk',
            'amazon.de',
            'amazon.fr',
            'amazon.it',
            'amazon.es',
            'amazon.ca',
            'amazonaws.com',
        ], 'amazon');
    }

    private function hasAuth(MarketplaceAccount $account): bool
    {
        $creds = is_array($account->credentials) ? $account->credentials : [];
        return (string) Arr::get($creds, 'access_token', '') !== ''
            || (string) Arr::get($creds, 'token', '') !== '';
    }

    private function authRequest(MarketplaceAccount $account)
    {
        $creds = is_array($account->credentials) ? $account->credentials : [];
        $request = $this->httpClient($account);

        $accessToken = (string) Arr::get($creds, 'access_token', '');
        if ($accessToken !== '') {
            return $request->withToken($accessToken);
        }

        $token = (string) Arr::get($creds, 'token', '');
        if ($token !== '') {
            return $request->withToken($token);
        }

        return $request;
    }
}
