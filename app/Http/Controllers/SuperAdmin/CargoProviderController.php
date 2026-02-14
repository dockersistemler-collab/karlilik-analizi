<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CargoProviderController extends Controller
{
    public function index(): View
    {
        $providers = (array) config('cargo_providers.providers', []);
        $providerKeys = array_keys($providers);

        $modules = Module::query()
            ->whereIn('code', array_map(fn ($k) => "integration.cargo.{$k}", $providerKeys))
            ->get()
            ->keyBy('code');

        return view('super-admin.cargo.providers.index', [
            'providers' => $providers,
            'modules' => $modules,
        ]);
    }

    public function toggle(Request $request, string $providerKey): RedirectResponse
    {
        $providers = (array) config('cargo_providers.providers', []);
        if (!array_key_exists($providerKey, $providers)) {
            return back()->with('error', 'Sağlayıcı bulunamadı.');
        }
$moduleCode = "integration.cargo.{$providerKey}";
        $module = Module::query()->where('code', $moduleCode)->first();

        if (!$module) {
            $module = Module::query()->create([
                'code' => $moduleCode,
                'name' => ($providers[$providerKey]['label'] ?? $providerKey).' Entegrasyonu',
                'description' => 'Kargo sağlayıcı entegrasyonu.',
                'type' => 'integration',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]);
        } else {
            $module->is_active = !$module->is_active;
            $module->save();
        }

        return back()->with('success', 'Sağlayıcı durumu güncellendi.');
    }
}



