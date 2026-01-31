<?php

namespace App\Services\Purchases;

use App\Models\ModulePurchase;
use App\Services\Entitlements\EntitlementService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ModulePurchaseService
{
    public function __construct(private readonly EntitlementService $entitlements)
    {
    }

    public function markPaid(ModulePurchase $purchase): ModulePurchase
    {
        return DB::transaction(function () use ($purchase) {
            $purchase->refresh();

            if ($purchase->status === 'paid') {
                return $purchase;
            }

            $purchase->loadMissing(['user', 'module']);

            $startsAt = $purchase->starts_at ?? Carbon::now();
            $endsAt = $purchase->ends_at ?? $this->calculateEndsAt($startsAt, $purchase->period);

            $purchase->status = 'paid';
            $purchase->starts_at = $startsAt;
            $purchase->ends_at = $endsAt;
            $purchase->save();

            $this->entitlements->grantModule(
                $purchase->user,
                $purchase->module->code,
                $purchase->ends_at,
                is_array($purchase->meta) ? $purchase->meta : [],
                $purchase->starts_at
            );

            return $purchase;
        });
    }

    public function markCancelled(ModulePurchase $purchase): ModulePurchase
    {
        return DB::transaction(function () use ($purchase) {
            $purchase->refresh();
            $purchase->loadMissing(['user', 'module']);

            if ($purchase->status === 'cancelled') {
                return $purchase;
            }

            $purchase->status = 'cancelled';
            $purchase->save();

            $this->entitlements->revokeModule($purchase->user, $purchase->module->code);

            return $purchase;
        });
    }

    public function markRefunded(ModulePurchase $purchase): ModulePurchase
    {
        return DB::transaction(function () use ($purchase) {
            $purchase->refresh();
            $purchase->loadMissing(['user', 'module']);

            if ($purchase->status === 'refunded') {
                return $purchase;
            }

            $purchase->status = 'refunded';
            $purchase->save();

            $this->entitlements->revokeModule($purchase->user, $purchase->module->code);

            return $purchase;
        });
    }

    private function calculateEndsAt(Carbon $startsAt, string $period): ?Carbon
    {
        return match ($period) {
            'monthly' => $startsAt->copy()->addMonth(),
            'yearly' => $startsAt->copy()->addYear(),
            'one_time' => null,
            default => null,
        };
    }
}

