<?php

namespace App\Integrations\Marketplaces;

use App\Integrations\Marketplaces\DTO\AdjustmentDTO;
use App\Integrations\Marketplaces\DTO\CoreOrderItemDTO;
use App\Integrations\Marketplaces\Support\DateRange;
use App\Models\MarketplaceAccount;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class N11Adapter extends BaseMarketplaceAdapter
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
            Log::warning('N11 orders fetch skipped: missing credentials/base_url', [
                'tenant_id' => $account->tenant_id,
                'account_id' => $account->id,
            ]);
            return [];
        }

        $url = rtrim($baseUrl, '/') . '/orders';
        $query = [
            // TODO: N11 order API tarih filtreleri uyarlanacak.
            'startDate' => $range->from->toDateTimeString(),
            'endDate' => $range->to->toDateTimeString(),
        ];

        $response = $this->authRequest($account)->get($url, $query);
        if (!$response->successful()) {
            Log::warning('N11 orders fetch failed', [
                'tenant_id' => $account->tenant_id,
                'account_id' => $account->id,
                'status' => $response->status(),
            ]);
            return [];
        }

        $payload = $response->json();
        return is_array($payload) ? ($payload['items'] ?? $payload['content'] ?? $payload) : [];
    }

    public function fetchReturns(MarketplaceAccount $account, DateRange $range): iterable
    {
        $baseUrl = $this->resolveBaseUrl($account);
        if ($baseUrl === '' || !$this->hasAuth($account)) {
            return [];
        }

        $url = rtrim($baseUrl, '/') . '/returns';
        $query = [
            'startDate' => $range->from->toDateTimeString(),
            'endDate' => $range->to->toDateTimeString(),
        ];

        $response = $this->authRequest($account)->get($url, $query);
        if (!$response->successful()) {
            Log::warning('N11 returns fetch failed', [
                'tenant_id' => $account->tenant_id,
                'account_id' => $account->id,
                'status' => $response->status(),
            ]);
            return [];
        }

        $payload = $response->json();
        return is_array($payload) ? ($payload['items'] ?? $payload['content'] ?? $payload) : [];
    }

    public function fetchFees(MarketplaceAccount $account, DateRange $range): iterable
    {
        $baseUrl = $this->resolveBaseUrl($account);
        if ($baseUrl === '' || !$this->hasAuth($account)) {
            return [];
        }

        $url = rtrim($baseUrl, '/') . '/fees';
        $query = [
            'startDate' => $range->from->toDateTimeString(),
            'endDate' => $range->to->toDateTimeString(),
        ];

        $response = $this->authRequest($account)->get($url, $query);
        if (!$response->successful()) {
            Log::warning('N11 fees fetch failed', [
                'tenant_id' => $account->tenant_id,
                'account_id' => $account->id,
                'status' => $response->status(),
            ]);
            return [];
        }

        $payload = $response->json();
        return is_array($payload) ? ($payload['items'] ?? $payload['content'] ?? $payload) : [];
    }

    public function mapOrderItemToCore(array $raw): CoreOrderItemDTO
    {
        $orderDate = $this->parseDate($this->firstNotNull($raw, [
            'order_date',
            'orderDate',
            'orderDateTime',
            'createdDate',
            'createdAt',
        ])) ?? CarbonImmutable::now();

        return new CoreOrderItemDTO(
            marketplace: 'n11',
            orderId: $this->stringOrDefault($this->firstNotNull($raw, [
                'order_id',
                'orderId',
                'orderNumber',
                'id',
            ]), 'unknown'),
            orderItemId: $this->stringOrDefault($this->firstNotNull($raw, [
                'order_item_id',
                'orderItemId',
                'lineItemId',
                'lineId',
                'id',
            ]), 'unknown'),
            orderDate: $orderDate,
            shipDate: $this->parseDate($this->firstNotNull($raw, ['ship_date', 'shipDate', 'shipmentDate'])),
            deliveredDate: $this->parseDate($this->firstNotNull($raw, ['delivered_date', 'deliveredDate', 'deliveryDate'])),
            sku: $this->nullableString($this->firstNotNull($raw, ['sku', 'sellerSku', 'barcode'])),
            productId: $this->nullableInt($this->firstNotNull($raw, ['product_id', 'productId'])),
            variant: $this->nullableString($this->firstNotNull($raw, ['variant', 'size', 'color'])),
            quantity: $this->nullableInt($this->firstNotNull($raw, ['quantity', 'qty'])) ?? 1,
            currency: $this->stringOrDefault($this->firstNotNull($raw, ['currency', 'currencyCode']), 'TRY'),
            fxRate: $this->nullableFloat($this->firstNotNull($raw, ['fx_rate', 'fxRate'])) ?? 1.0,
            grossSales: $this->nullableFloat($this->firstNotNull($raw, ['gross_sales', 'grossAmount', 'amount', 'totalPrice', 'price'])) ?? 0.0,
            discounts: $this->nullableFloat($this->firstNotNull($raw, ['discounts', 'discountAmount', 'discount'])) ?? 0.0,
            refunds: $this->nullableFloat($this->firstNotNull($raw, ['refunds', 'refundAmount'])) ?? 0.0,
            commissionFee: $this->nullableFloat($this->firstNotNull($raw, ['commission_fee', 'commissionAmount', 'commission'])) ?? 0.0,
            paymentFee: $this->nullableFloat($this->firstNotNull($raw, ['payment_fee', 'paymentAmount', 'serviceFee'])) ?? 0.0,
            shippingFee: $this->nullableFloat($this->firstNotNull($raw, ['shipping_fee', 'shippingAmount', 'cargoAmount'])) ?? 0.0,
            otherFees: $this->nullableFloat($this->firstNotNull($raw, ['other_fees', 'otherFees'])) ?? 0.0,
            vatAmount: $this->nullableFloat($this->firstNotNull($raw, ['vat_amount', 'vatAmount'])),
            taxAmount: $this->nullableFloat($this->firstNotNull($raw, ['tax_amount', 'taxAmount'])),
            cogsUnit: $this->nullableFloat($this->firstNotNull($raw, ['cogs_unit', 'cogsUnit'])),
            status: $this->stringOrDefault($this->firstNotNull($raw, ['status', 'lineItemStatus', 'orderStatus']), 'paid')
        );
    }

    public function mapReturnToCoreAdjustments(array $raw): AdjustmentDTO
    {
        $occurredAt = $this->parseDate($this->firstNotNull($raw, [
            'occurred_at',
            'occurredAt',
            'createdDate',
            'createdAt',
            'returnDate',
        ])) ?? CarbonImmutable::now();

        return new AdjustmentDTO(
            marketplace: 'n11',
            orderItemId: $this->stringOrDefault($this->firstNotNull($raw, [
                'order_item_id',
                'orderItemId',
                'lineItemId',
                'lineId',
                'id',
            ]), 'unknown'),
            type: 'refund',
            amount: $this->nullableFloat($this->firstNotNull($raw, ['amount', 'refund_amount', 'refundAmount'])) ?? 0.0,
            currency: $this->stringOrDefault($this->firstNotNull($raw, ['currency', 'currencyCode']), 'TRY'),
            occurredAt: $occurredAt
        );
    }

    public function mapFeeToCoreAdjustments(array $raw): AdjustmentDTO
    {
        $occurredAt = $this->parseDate($this->firstNotNull($raw, [
            'occurred_at',
            'occurredAt',
            'createdDate',
            'createdAt',
            'transactionDate',
        ])) ?? CarbonImmutable::now();

        return new AdjustmentDTO(
            marketplace: 'n11',
            orderItemId: $this->stringOrDefault($this->firstNotNull($raw, [
                'order_item_id',
                'orderItemId',
                'lineItemId',
                'lineId',
                'id',
            ]), 'unknown'),
            type: $this->stringOrDefault($this->firstNotNull($raw, ['fee_type', 'feeType', 'type']), 'fee'),
            amount: $this->nullableFloat($this->firstNotNull($raw, ['amount', 'fee_amount', 'feeAmount', 'value'])) ?? 0.0,
            currency: $this->stringOrDefault($this->firstNotNull($raw, ['currency', 'currencyCode']), 'TRY'),
            occurredAt: $occurredAt
        );
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (is_int($value) || is_float($value)) {
            $timestamp = (float) $value;
            if ($timestamp > 1_000_000_000_000) {
                $timestamp /= 1000;
            }
            return CarbonImmutable::createFromTimestampUTC((int) $timestamp);
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }

            if (is_numeric($trimmed)) {
                $numeric = (float) $trimmed;
                if ($numeric > 1_000_000_000_000) {
                    $numeric /= 1000;
                }
                return CarbonImmutable::createFromTimestampUTC((int) $numeric);
            }

            try {
                return CarbonImmutable::parse($trimmed);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $keys
     */
    private function firstNotNull(array $raw, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = Arr::get($raw, $key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $normalized = str_replace([',', ' '], ['.', ''], $value);
            return is_numeric($normalized) ? (float) $normalized : null;
        }

        return null;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);
        return $string === '' ? null : $string;
    }

    private function stringOrDefault(mixed $value, string $default): string
    {
        $string = $this->nullableString($value);
        return $string ?? $default;
    }

    private function resolveBaseUrl(MarketplaceAccount $account): string
    {
        $creds = is_array($account->credentials) ? $account->credentials : [];
        $baseUrl = (string) Arr::get($creds, 'base_url', '');
        return $this->allowlistedBaseUrl($account, $baseUrl, [
            'n11.com',
        ], 'n11');
    }

    private function hasAuth(MarketplaceAccount $account): bool
    {
        $creds = is_array($account->credentials) ? $account->credentials : [];
        return (string) Arr::get($creds, 'api_key', '') !== ''
            || (string) Arr::get($creds, 'token', '') !== '';
    }

    private function authRequest(MarketplaceAccount $account)
    {
        $creds = is_array($account->credentials) ? $account->credentials : [];
        $request = $this->httpClient($account);

        $apiKey = (string) Arr::get($creds, 'api_key', '');
        $apiSecret = (string) Arr::get($creds, 'api_secret', '');
        if ($apiKey !== '' && $apiSecret !== '') {
            return $request->withBasicAuth($apiKey, $apiSecret);
        }

        $token = (string) Arr::get($creds, 'token', '');
        if ($token !== '') {
            return $request->withToken($token);
        }

        return $request;
    }
}
