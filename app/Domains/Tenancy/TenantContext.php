<?php

namespace App\Domains\Tenancy;

class TenantContext
{
    private ?int $tenantId = null;

    public function setTenantId(?int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function tenantId(): ?int
    {
        return $this->tenantId;
    }
}

