<?php

namespace App\Domains\Reconciliation\Actions;

use App\Domains\Settlements\Services\ReconciliationService;

class ReconcilePayoutsAction
{
    public function __construct(private readonly ReconciliationService $service)
    {
    }

    public function execute(int $accountId): void
    {
        $this->reconcileByAccount($accountId);
    }

    public function reconcileByAccount(int $accountId, ?float $tolerance = null): void
    {
        $this->service->reconcileByAccount($accountId, $tolerance);
    }

    public function executeByAccount(int $accountId): void
    {
        $this->reconcileByAccount($accountId);
    }

    public function reconcileOne(int $payoutId, ?float $tolerance = null): void
    {
        $this->service->reconcileOne($payoutId, $tolerance);
    }

    public function executeOne(int $payoutId): void
    {
        $this->reconcileOne($payoutId);
    }
}

