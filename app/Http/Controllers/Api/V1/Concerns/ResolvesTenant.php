<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Domains\Tenancy\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ResolvesTenant
{
    protected function currentTenantId(): int
    {
        $tenantId = app(TenantContext::class)->tenantId();
        if (!$tenantId) {
            throw new HttpException(400, 'Tenant context is missing. Pass X-Tenant-Id for super admin requests.');
        }

        return $tenantId;
    }
}

