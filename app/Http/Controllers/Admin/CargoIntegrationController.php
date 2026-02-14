<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CargoProviderInstallation;
use App\Models\Module;
use App\Services\Cargo\CargoProviderManager;
use App\Services\Entitlements\EntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CargoIntegrationController extends Controller
{
    public function index(Request $request, EntitlementService $entitlements): View
    {
        $user = $request->user();
        abort_unless($user, 401);

        $providers = (array) config('cargo_providers.providers', []);
        $providerKeys = array_keys($providers);

        $modules = Module::query()
            ->whereIn('code', array_merge(
                ['feature.cargo_tracking'],
                array_map(fn ($k) => "integration.cargo.{$k}", $providerKeys)
            ))
            ->get()
            ->keyBy('code');

        $installations = $user->cargoProviderInstallations()->get()->keyBy('provider_key');
        $hasAccess = $entitlements->hasModule($user, 'feature.cargo_tracking');
        $providerAccess = [];
        foreach ($providerKeys as $key) {
            $providerAccess[$key] = $entitlements->hasModule($user, "integration.cargo.{$key}");
        }

        return view('admin.settings.cargo', [
            'providers' => $providers,
            'modules' => $modules,
            'installations' => $installations,
            'hasAccess' => $hasAccess,
            'providerAccess' => $providerAccess,
        ]);
    }

    public function update(Request $request, string $providerKey, EntitlementService $entitlements): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $providers = (array) config('cargo_providers.providers', []);
        if (!array_key_exists($providerKey, $providers)) {
            throw ValidationException::withMessages(['provider' => 'Sağlayıcı bulunamadı.']);
        }

        if (!$entitlements->hasModule($user, 'feature.cargo_tracking')) {
            abort(403);
        }
$moduleCode = "integration.cargo.{$providerKey}";
        if (!$entitlements->hasModule($user, $moduleCode)) {
            return back()->withErrors(['provider' => 'Bu sağlayıcı için lisansınız yok.']);
        }
$provider = $providers[$providerKey];
        $credentialsMeta = is_array($provider['credentials'] ?? null) ? $provider['credentials'] : [];

        $rules = [
            'is_active' => 'nullable|boolean',
        ];

        foreach ($credentialsMeta as $key => $meta) {
            if (!is_string($key) || $key === '') {
                continue;
            }
$required = (bool) ($meta['required'] ?? true);
            $rules[$key] = ($required ? 'required' : 'nullable') . '|string|max:500';
        }
$validated = $request->validate($rules);
        $credentials = [];
        foreach ($credentialsMeta as $key => $meta) {
            if (!is_string($key) || $key === '') {
                continue;
            }
$credentials[$key] = $validated[$key] ?? null;
        }

        CargoProviderInstallation::query()->updateOrCreate(
            ['user_id' => $user->id, 'provider_key' => $providerKey],
            [
                'credentials_json' => $credentials,
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        return back()->with('success', 'Kargo sağlayıcı ayarları güncellendi.');
    }

    public function test(Request $request, string $providerKey, CargoProviderManager $manager): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        try {
            $provider = $manager->resolve($user, $providerKey);
            $result = $provider->track('TEST');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
$installation = CargoProviderInstallation::query()
            ->where('user_id', $user->id)
            ->where('provider_key', $providerKey)
            ->first();

        if ($installation) {
            $meta = is_array($installation->meta) ? $installation->meta : [];
            $meta['last_tested_at'] = now()->toDateTimeString();
            $meta['last_test_result'] = $result->success ? 'success' : 'fail';
            $installation->meta = $meta;
            $installation->save();
        }

        if ($result->success) {
            return back()->with('success', 'Bağlantı testi başarılı.');
        }

        return back()->with('error', 'Bağlantı testi başarısız.');
    }

}






