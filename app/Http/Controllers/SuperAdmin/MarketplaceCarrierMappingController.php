<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\MarketplaceCarrierMapping;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MarketplaceCarrierMappingController extends Controller
{
    public function index(Request $request): View
    {
        $query = MarketplaceCarrierMapping::query()->orderBy('marketplace_code')->orderBy('external_carrier_code');

        if ($request->filled('marketplace_code')) {
            $query->where('marketplace_code', $request->marketplace_code);
        }
        if ($request->filled('provider_key')) {
            $query->where('provider_key', $request->provider_key);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }
$mappings = $query->paginate(20)->withQueryString();
        $marketplaces = Marketplace::query()->orderBy('name')->get();
        $providers = (array) config('cargo_providers.providers', []);

        return view('super-admin.cargo.mappings.index', compact('mappings', 'marketplaces', 'providers'));
    }

    public function create(): View
    {
        $marketplaces = Marketplace::query()->orderBy('name')->get();
        $providers = (array) config('cargo_providers.providers', []);

        return view('super-admin.cargo.mappings.create', compact('marketplaces', 'providers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        try {
            MarketplaceCarrierMapping::query()->create($data);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                return back()
                    ->withErrors(['external_carrier_code' => 'Bu pazaryeri için bu kargo zaten eşlenmiş.'])
                    ->withInput();
            }
            throw $e;
        }

        return redirect()->route('super-admin.cargo.mappings.index')->with('success', 'Mapping oluşturuldu.');
    }

    public function edit(MarketplaceCarrierMapping $mapping): View
    {
        $marketplaces = Marketplace::query()->orderBy('name')->get();
        $providers = (array) config('cargo_providers.providers', []);

        return view('super-admin.cargo.mappings.edit', compact('mapping', 'marketplaces', 'providers'));
    }

    public function update(Request $request, MarketplaceCarrierMapping $mapping): RedirectResponse
    {
        $data = $this->validatePayload($request);
        try {
            $mapping->update($data);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                return back()
                    ->withErrors(['external_carrier_code' => 'Bu pazaryeri için bu kargo zaten eşlenmiş.'])
                    ->withInput();
            }
            throw $e;
        }

        return redirect()->route('super-admin.cargo.mappings.index')->with('success', 'Mapping güncellendi.');
    }

    public function destroy(MarketplaceCarrierMapping $mapping): RedirectResponse
    {
        $mapping->delete();

        return back()->with('success', 'Mapping silindi.');
    }

    /**
     * @return array<string,mixed>
     */
    private function validatePayload(Request $request): array
    {
        $providers = (array) config('cargo_providers.providers', []);
        $providerKeys = array_keys($providers);

        $validated = $request->validate(['marketplace_code' => 'required|string|max:100',
            'external_carrier_code' => 'required|string|max:255',
            'provider_key' => 'required|string|max:100',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if (!in_array($validated['provider_key'], $providerKeys, true)) {
            throw ValidationException::withMessages(['provider_key' => 'Geçersiz sağlayıcı.']);
        }
$validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $errorCode = $e->errorInfo[1] ?? null;

        return $sqlState === '23000' && (int) $errorCode === 1062;
    }
}
