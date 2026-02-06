<?php

namespace App\Services\Cargo;

use App\Models\CargoProviderInstallation;
use App\Models\User;
use App\Services\Cargo\Providers\ArasCargoProvider;
use App\Services\Cargo\Providers\CargoProviderInterface;
use App\Services\Cargo\Providers\TrendyolExpressCargoProvider;
use App\Services\Cargo\Providers\YurticiCargoProvider;
use App\Services\Entitlements\EntitlementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class CargoProviderManager
{
    public function __construct(private readonly EntitlementService $entitlements)
    {
    }

    public function resolve(User $user, string $providerKey): CargoProviderInterface
    {
        $providerKey = trim($providerKey);
        if ($providerKey === '') {
            throw ValidationException::withMessages(['provider_key' => 'Sağlayıcı anahtarı gerekli.']);
        }

        if (!$this->entitlements->hasModule($user, 'feature.cargo_tracking')) {
            throw new AuthorizationException('Kargo takip modülü aktif değil.');
        }
$moduleCode = "integration.cargo.{$providerKey}";
        if (!$this->entitlements->hasModule($user, $moduleCode)) {
            throw new AuthorizationException("Bu sağlayıcı için lisans gerekli: {$moduleCode}");
        }
$providers = (array) config('cargo_providers.providers', []);
        if (!array_key_exists($providerKey, $providers)) {
            throw ValidationException::withMessages(['provider_key' => 'Bilinmeyen kargo sağlayıcısı.']);
        }
$installation = CargoProviderInstallation::query()
            ->where('user_id', $user->id)
            ->where('provider_key', $providerKey)
            ->where('is_active', true)
            ->first();

        if (!$installation) {
            throw ValidationException::withMessages(['provider_key' => 'Kargo sağlayıcı kurulumu bulunamadı.']);
        }
$credentials = is_array($installation->credentials_json) ? $installation->credentials_json : [];

        return match ($providerKey) {
            'trendyol_express' => new TrendyolExpressCargoProvider($credentials),
            'aras' => new ArasCargoProvider($credentials),
            'yurtici' => new YurticiCargoProvider($credentials),
            default => throw ValidationException::withMessages(['provider_key' => 'Sağlayıcı henüz desteklenmiyor.']),
        };
    }
}
