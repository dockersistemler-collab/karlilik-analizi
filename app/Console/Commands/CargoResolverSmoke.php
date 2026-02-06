<?php

namespace App\Console\Commands;

use App\Models\Marketplace;
use App\Models\Order;
use App\Models\User;
use App\Services\Cargo\CargoProviderResolver;
use App\Services\Entitlements\EntitlementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CargoResolverSmoke extends Command
{
    protected $signature = 'cargo:resolver-smoke {--unmapped} {--no-installation}';

    protected $description = 'Seeds cargo fixture data and runs a resolver smoke test.';

    public function handle(CargoProviderResolver $resolver, EntitlementService $entitlements): int
    {
        $this->info('Preparing cargo resolver smoke data...');

        $user = User::query()->find(2);
        if (!$user) {
            $this->error('User #2 not found.');
            return self::FAILURE;
        }
$marketplace = Marketplace::query()->where('code', 'trendyol')->first();
        if (!$marketplace) {
            $this->error('Marketplace "trendyol" not found.');
            return self::FAILURE;
        }
$this->cleanupSmokeData();

        try {
            $entitlements->grantModule($user, 'feature.cargo_tracking');
            $entitlements->grantModule($user, 'integration.cargo.trendyol_express');
            $entitlements->grantModule($user, 'integration.cargo.aras');
        } catch (Throwable $e) {
            $this->warn('Module grant failed: '.$e->getMessage());
        }
$scenario = $this->resolveScenario();
        if ($scenario === 'default' || $scenario === 'no-installation') {
            $this->ensureMappings();
        }
        if ($scenario === 'default') {
            $this->ensureInstallations();
        }
$orders = $this->createOrders($user->id, $marketplace->id);

        foreach ($orders as $order) {
            $resolved = $resolver->resolveForOrder($order);
            $order->refresh();

            $carrierName = $this->resolveCarrierName($order) ?? 'null';
            $providerKey = $resolved['provider_key'] ?? $order->shipment_provider_key   'null';
            $status = $this->normalizeStatus($order->shipment_status);

            $this->line(sprintf('order_id=%s carrier=%s provider_key=%s shipment_status=%s',
                $order->id,
                $carrierName,
                $providerKey,
                $status
            ));
        }

        return self::SUCCESS;
    }

    private function resolveScenario(): string
    {
        if ($this->option('unmapped')) {
            return 'unmapped';
        }

        if ($this->option('no-installation')) {
            return 'no-installation';
        }

        return 'default';
    }

    private function cleanupSmokeData(): void
    {
        Order::query()
            ->where('order_number', 'like', 'SMOKE-%')
            ->orWhere('marketplace_data->_smoke', true)
            ->delete();

        if (Schema::hasColumn('marketplace_carrier_mappings', 'meta')) {
            \App\Models\MarketplaceCarrierMapping::query()
                ->where('marketplace_code', 'trendyol')
                ->where('meta->_smoke', true)
                ->delete();
        }

        if (Schema::hasColumn('cargo_provider_installations', 'meta')) {
            \App\Models\CargoProviderInstallation::query()
                ->where('user_id', 2)
                ->where('meta->_smoke', true)
                ->delete();
        }
    }

    /**
     * @return array<int,Order>
     */
    private function createOrders(int $userId, int $marketplaceId): array
    {
        $orderA = Order::query()->updateOrCreate(
            ['marketplace_order_id' => 'SMOKE-A'],
            [
                'user_id' => $userId,
                'marketplace_id' => $marketplaceId,
                'order_number' => 'SMOKE-A',
                'status' => 'pending',
                'total_amount' => 100,
                'commission_amount' => 0,
                'net_amount' => 100,
                'currency' => 'TRY',
                'customer_name' => 'Smoke Test A',
                'customer_email' => 'cargo-smoke-a@example.com',
                'customer_phone' => '0000000000',
                'shipping_address' => 'Smoke Address A',
                'billing_address' => 'Smoke Address A',
                'cargo_company' => 'Trendyol Express',
                'order_date' => now(),
                'marketplace_data' => [
                    '_smoke' => true,
                ],
            ]
        );

        $orderB = Order::query()->updateOrCreate(
            ['marketplace_order_id' => 'SMOKE-B'],
            [
                'user_id' => $userId,
                'marketplace_id' => $marketplaceId,
                'order_number' => 'SMOKE-B',
                'status' => 'pending',
                'total_amount' => 120,
                'commission_amount' => 0,
                'net_amount' => 120,
                'currency' => 'TRY',
                'customer_name' => 'Smoke Test B',
                'customer_email' => 'cargo-smoke-b@example.com',
                'customer_phone' => '0000000000',
                'shipping_address' => 'Smoke Address B',
                'billing_address' => 'Smoke Address B',
                'cargo_company' => null,
                'order_date' => now(),
                'marketplace_data' => [
                    '_smoke' => true,
                    'shippingProvider' => 'Aras',
                ],
            ]
        );

        return [$orderA->fresh(), $orderB->fresh()];
    }

    private function ensureMappings(): void
    {
        $mappings = [
            [
                'marketplace_code' => 'trendyol',
                'external_carrier_code' => 'Trendyol Express',
                'provider_key' => 'trendyol_express',
                'priority' => null,
                'is_active' => true,
            ],
            [
                'marketplace_code' => 'trendyol',
                'external_carrier_code' => 'Aras',
                'provider_key' => 'aras',
                'priority' => null,
                'is_active' => true,
            ],
        ];

        foreach ($mappings as $mapping) {
            \App\Models\MarketplaceCarrierMapping::query()->updateOrCreate(
                [
                    'marketplace_code' => $mapping['marketplace_code'],
                    'external_carrier_code' => $mapping['external_carrier_code'],
                ],
                array_merge($mapping, [
                    'meta' => ['_smoke' => true],
                ])
            );
        }
    }

    private function ensureInstallations(): void
    {
        $installations = [
            'trendyol_express',
            'aras',
        ];

        foreach ($installations as $providerKey) {
            \App\Models\CargoProviderInstallation::query()->updateOrCreate(
                [
                    'user_id' => 2,
                    'provider_key' => $providerKey,
                ],
                [
                    'credentials_json' => ['test' => true, '_smoke' => true],
                    'meta' => ['_smoke' => true],
                    'is_active' => true,
                ]
            );
        }
    }

    private function normalizeStatus(?string $status): string
    {
        if (in_array($status, ['created', 'in_transit', 'delivered', 'cancelled'], true)) {
            return 'OK';
        }

        if (in_array($status, ['unmapped_carrier', 'provider_not_installed'], true)) {
            return 'FAIL:'.$status;
        }

        return $status ?: 'OK';
    }

    private function resolveCarrierName(Order $order): ?string
    {
        if (is_string($order->cargo_company) && trim($order->cargo_company) !== '') {
            return trim($order->cargo_company);
        }
$marketplaceData = is_array($order->marketplace_data) ? $order->marketplace_data : [];
        foreach (['cargoCompany', 'shippingProvider', 'cargoProviderName', 'carrier'] as $key) {
            $value = $marketplaceData[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
