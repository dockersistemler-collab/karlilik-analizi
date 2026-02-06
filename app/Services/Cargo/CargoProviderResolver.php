<?php

namespace App\Services\Cargo;

use App\Models\CargoProviderInstallation;
use App\Models\MarketplaceCarrierMapping;
use App\Models\Order;
use App\Support\Cargo\CarrierNormalizer;
use Illuminate\Support\Facades\Log;

class CargoProviderResolver
{
    public function __construct(private readonly CargoProviderManager $manager)
    {
    }

    /**
     * @return array{provider_key:string,provider:\App\Services\Cargo\Providers\CargoProviderInterface}|null
     */
    public function resolveForOrder(Order $order): ?array
    {
        $order->loadMissing('marketplace');

        $marketplaceCode = $order->marketplace?->code ?: null;
        if (!$marketplaceCode) {
            Log::warning('Cargo resolver: marketplace code missing.', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ]);
            return null;
        }
$carrierNameRaw = $this->resolveCarrierCode($order);
        $carrierNorm = CarrierNormalizer::normalizeCarrier($carrierNameRaw);
        if (!$carrierNorm) {
            $order->shipment_status = 'unmapped_carrier';
            $order->save();
            Log::warning('Cargo resolver: carrier code missing.', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'marketplace_code' => $marketplaceCode,
                'carrier_name' => $carrierNameRaw,
                'carrier_norm' => $carrierNorm,
                'marketplace_data_keys' => $this->marketplaceDataKeys($order),
            ]);
            return null;
        }
$baseQuery = MarketplaceCarrierMapping::query()
            ->where('marketplace_code', $marketplaceCode)
            ->where('is_active', true)
            ->orderByRaw('priority is null')
            ->orderBy('priority');

        $mapping = (clone $baseQuery)
            ->where('external_carrier_code_normalized', $carrierNorm)
            ->first();

        if (!$mapping && $carrierNameRaw) {
            $mapping = (clone $baseQuery)
                ->whereNull('external_carrier_code_normalized')
                ->whereRaw('LOWER(external_carrier_code) = ?', [$this->normalizeLower($carrierNameRaw)])
                ->first();
        }

        if (!$mapping) {
            $order->shipment_status = 'unmapped_carrier';
            $order->save();
            Log::warning('Cargo resolver: mapping not found.', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'marketplace_code' => $marketplaceCode,
                'carrier_name' => $carrierNameRaw,
                'carrier_norm' => $carrierNorm,
                'marketplace_data_keys' => $this->marketplaceDataKeys($order),
            ]);
            return null;
        }
$installation = CargoProviderInstallation::query()
            ->where('user_id', $order->user_id)
            ->where('provider_key', $mapping->provider_key)
            ->where('is_active', true)
            ->exists();

        if (!$installation) {
            $order->shipment_status = 'provider_not_installed';
            $order->save();
            Log::warning('Cargo resolver: provider installation not found.', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'provider_key' => $mapping->provider_key,
            ]);
            return null;
        }

        if ($order->shipment_provider_key !== $mapping->provider_key) {
            $order->shipment_provider_key = $mapping->provider_key;
            $order->save();
        }

        try {
            $provider = $this->manager->resolve($order->user, $mapping->provider_key);
        } catch (\Throwable $e) {
            Log::warning('Cargo resolver: provider resolve failed.', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'provider_key' => $mapping->provider_key,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        return [
            'provider_key' => $mapping->provider_key,
            'provider' => $provider,
        ];
    }

    private function resolveCarrierCode(Order $order): ?string
    {
        if (is_string($order->cargo_company) && trim($order->cargo_company) !== '') {
            return trim($order->cargo_company);
        }
$marketplaceData = is_array($order->marketplace_data) ? $order->marketplace_data : [];
        foreach (['cargoCompany', 'shippingProvider', 'cargoProviderName', 'carrier'] as $key) {
            $val = $marketplaceData[$key] ?? null;
            if (is_string($val) && trim($val) !== '') {
                return trim($val);
            }
        }

        return null;
    }

    /**
     * @return array<int,string>
     */
    private function marketplaceDataKeys(Order $order): array
    {
        $marketplaceData = is_array($order->marketplace_data) ? $order->marketplace_data : [];
        return array_values(array_keys($marketplaceData));
    }

    private function normalizeLower(string $value): string
    {
        if (function_exists('mb_strtolower')) {
            $encoding = in_array('tr_TR', mb_list_encodings(), true) ? 'tr_TR' : 'UTF-8';
            return mb_strtolower($value, $encoding);
        }

        return strtolower($value);
    }
}
