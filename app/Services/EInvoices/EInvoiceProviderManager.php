<?php

namespace App\Services\EInvoices;

use App\Models\EInvoiceProviderInstallation;
use App\Models\User;
use App\Services\Entitlements\EntitlementService;
use App\Services\EInvoices\Providers\CustomHttpProvider;
use App\Services\EInvoices\Providers\EInvoiceProviderInterface;
use App\Services\EInvoices\Providers\NullProvider;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class EInvoiceProviderManager
{
    public function __construct(private readonly EntitlementService $entitlements)
    {
    }

    public function forUser(User $user): EInvoiceProviderInterface
    {
        $setting = $user->einvoiceSetting;
        $key = (string) ($setting?->active_provider_key ?: 'null');

        if (!$this->entitlements->hasModule($user, 'feature.einvoice')) {
            throw new AuthorizationException('E-Fatura modülü aktif değil.');
        }

        if ($key !== 'null') {
            $moduleCode = "integration.einvoice.{$key}";
            if (!$this->entitlements->hasModule($user, $moduleCode)) {
                throw new AuthorizationException("Bu sağlayıcı için lisans gerekli: {$moduleCode}");
            }
        }

        return match ($key) {
            'custom' => $this->customProviderForUser($user),
            'null' => new NullProvider(),
            default => throw ValidationException::withMessages([
                'active_provider_key' => 'Bilinmeyen sağlayıcı.',
            ]),
        };
    }

    private function customProviderForUser(User $user): EInvoiceProviderInterface
    {
        $installation = EInvoiceProviderInstallation::query()
            ->where('user_id', $user->id)
            ->where('provider_key', 'custom')
            ->where('status', 'active')
            ->first();

        if (!$installation) {
            throw ValidationException::withMessages([
                'provider' => 'Custom provider kurulumu bulunamadı.',
            ]);
        }
$credentials = is_array($installation->credentials) ? $installation->credentials : [];
        return new CustomHttpProvider($credentials);
    }
}

