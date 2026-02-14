<?php

namespace App\Services\Modules;

use App\Models\Module;
use App\Models\User;
use App\Services\Entitlements\EntitlementService;

class ModuleGate
{
    public function __construct(
        private readonly EntitlementService $entitlements
    ) {
    }

    public function isActive(string $code): bool
    {
        return Module::query()
            ->where('code', trim($code))
            ->where('is_active', true)
            ->exists();
    }

    public function isEnabledForUser(?User $user, string $code): bool
    {
        if (!$user) {
            return false;
        }

        if (!$this->isActive($code)) {
            return false;
        }

        return $this->entitlements->hasModule($user, $code);
    }
}
