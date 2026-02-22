<?php

namespace App\Domains\Settlements\Rules;

class SettlementRuleSet
{
    public function __construct(
        public readonly string $cycleType = 'DELIVERY_PLUS_DAYS',
        public readonly int $cycleDays = 7,
        public readonly string $vatMode = 'INCLUSIVE',
        public readonly float $defaultServiceFee = 0,
        public readonly float $defaultServiceFeeVatRate = 20,
        public readonly string $shippingCalc = 'API_IF_INVOICED_ELSE_DESI',
        public readonly float $toleranceAmount = 1.0,
        public readonly float $tolerancePercent = 0.5,
    ) {
    }

    public static function fromArray(array $ruleset): self
    {
        $defaultService = (array) ($ruleset['default_service_fee'] ?? []);
        $tolerances = (array) ($ruleset['tolerances'] ?? []);

        return new self(
            cycleType: (string) ($ruleset['cycle_type'] ?? 'DELIVERY_PLUS_DAYS'),
            cycleDays: (int) ($ruleset['cycle_days'] ?? 7),
            vatMode: (string) ($ruleset['vat_mode'] ?? 'INCLUSIVE'),
            defaultServiceFee: (float) ($defaultService['amount'] ?? 0),
            defaultServiceFeeVatRate: (float) ($defaultService['vat_rate'] ?? 20),
            shippingCalc: (string) ($ruleset['shipping_calc'] ?? 'API_IF_INVOICED_ELSE_DESI'),
            toleranceAmount: (float) ($tolerances['amount'] ?? 1.0),
            tolerancePercent: (float) ($tolerances['percent'] ?? 0.5),
        );
    }
}

