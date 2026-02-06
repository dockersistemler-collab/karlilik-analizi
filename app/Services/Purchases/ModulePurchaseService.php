<?php

namespace App\Services\Purchases;

use App\Models\ModulePurchase;
use App\Services\Entitlements\EntitlementService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModulePurchaseService
{
    public function __construct(private readonly EntitlementService $entitlements)
    {
    }

    public function markPaid(ModulePurchase $purchase, array $meta = [], ?string $providerPaymentId = null): ModulePurchase
    {
        return DB::transaction(function () use ($purchase, $meta, $providerPaymentId) {
            $purchase->refresh();
            $purchase->loadMissing(['user', 'module']);

            if ($purchase->status === 'paid') {
                $dirty = false;

                if (!$purchase->provider_payment_id && is_string($providerPaymentId) && trim($providerPaymentId) !== '') {
                    $purchase->provider_payment_id = trim($providerPaymentId);
                    $dirty = true;
                }

                if (!empty($meta)) {
                    $existingMeta = is_array($purchase->meta) ? $purchase->meta : [];
                    $mergedMeta = array_merge($existingMeta, $meta);
                    $purchase->meta = empty($mergedMeta) ? null : $mergedMeta;
                    $dirty = true;
                }

                if ($dirty) {
                    $purchase->save();
                }

                return $purchase;
            }
$now = Carbon::now();

            $activeUserModule = $purchase->user
                ->userModules()
                ->where('module_id', $purchase->module_id)
                ->where('status', 'active')
                ->first();

            $baseStart = $now;
            if ($activeUserModule?->ends_at && $activeUserModule->ends_at->greaterThan($now)) {
                $baseStart = $activeUserModule->ends_at->copy();
            }
$endsAt = match ($purchase->period) {
                'monthly' => $baseStart->copy()->addMonth(),
                'yearly' => $baseStart->copy()->addYear(),
                'one_time' => null,
                default => null,
            };

            $existingMeta = is_array($purchase->meta) ? $purchase->meta : [];
            $mergedMeta = array_merge($existingMeta, $meta);
            if (empty($mergedMeta)) {
                $mergedMeta = null;
            }

            if (!$purchase->provider_payment_id && is_string($providerPaymentId) && trim($providerPaymentId) !== '') {
                $purchase->provider_payment_id = trim($providerPaymentId);
            }
$purchase->status = 'paid';
            $purchase->starts_at = $baseStart;
            $purchase->ends_at = $endsAt;
            $purchase->meta = $mergedMeta;

            if (Schema::hasColumn('module_purchases', 'paid_at')) {
                $purchase->setAttribute('paid_at', $now);
            }
$purchase->save();

            $this->entitlements->grantModule($purchase->user,
                $purchase->module->code,
                $endsAt,
                is_array($mergedMeta) ? $mergedMeta : [],
                $baseStart
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
}
