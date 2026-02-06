<?php

namespace App\Services\Notifications;

use App\Models\EmailSuppression;

class EmailSuppressionService
{
    public function isSuppressed(?int $tenantId, string $email): bool
    {
        return (bool) $this->findSuppression($tenantId, $email);
    }

    public function findSuppression(?int $tenantId, string $email): ?EmailSuppression
    {
        $email = $this->normalizeEmail($email);
        if ($email === '') {
            return null;
        }
$base = EmailSuppression::query()->where('email', $email);

        if ($tenantId) {
            $tenant = (clone $base)->where('tenant_id', $tenantId)->first();
            if ($tenant) {
                return $tenant;
            }
        }

        return $base->whereNull('tenant_id')->first();
    }

    /**
     * @param array<string,mixed> $meta
     */
    public function suppress(?int $tenantId, string $email, string $reason, ?string $source, array $meta = []): EmailSuppression
    {
        $email = $this->normalizeEmail($email);

        return EmailSuppression::updateOrCreate([
            'tenant_id' => $tenantId,
            'email' => $email,
        ], [
            'reason' => $reason,
            'source' => $source,
            'meta' => $meta,
        ]);
    }

    public function unsuppress(?int $tenantId, string $email): void
    {
        $email = $this->normalizeEmail($email);
        if ($email === '') {
            return;
        }

        EmailSuppression::query()
            ->where('email', $email)
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId), fn ($q) => $q->whereNull('tenant_id'))
            ->delete();
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
