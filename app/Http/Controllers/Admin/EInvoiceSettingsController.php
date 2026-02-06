<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EInvoiceProviderInstallation;
use App\Models\EInvoiceSetting;
use App\Services\Entitlements\EntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EInvoiceSettingsController extends Controller
{
    public function edit(Request $request, EntitlementService $entitlements): View
    {
        $user = $request->user();
        abort_unless($user, 401);

        $providers = $this->availableProvidersForUser($user, $entitlements);

        $setting = $user->einvoiceSetting ?: new EInvoiceSetting([
            'active_provider_key' => 'null',
            'auto_draft_enabled' => false,
            'auto_issue_enabled' => false,
            'draft_on_status' => 'approved',
            'issue_on_status' => 'shipped',
            'prefix' => 'EA',
            'default_vat_rate' => config('einvoices.default_vat_rate', 20),
        ]);

        $installations = $user->einvoiceProviderInstallations()->get()->keyBy('provider_key');

        return view('admin.settings.einvoice', compact('setting', 'providers', 'installations'));
    }

    public function update(Request $request, EntitlementService $entitlements): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate(['auto_draft_enabled' => 'nullable|boolean',
            'auto_issue_enabled' => 'nullable|boolean',
            'draft_on_status' => 'required|in:approved,shipped,delivered',
            'issue_on_status' => 'required|in:shipped,delivered',
            'prefix' => 'required|string|max:10',
            'default_vat_rate' => 'required|numeric|min:0|max:100',
            'active_provider_key' => 'nullable|string|max:50',

            // Installation (MVP: custom) ?? 'custom_base_url' => 'nullable|url|max:500',
            'custom_api_key' => 'nullable|string|max:500',
        ]);

        $validated['auto_draft_enabled'] = $request->boolean('auto_draft_enabled');
        $validated['auto_issue_enabled'] = $request->boolean('auto_issue_enabled');

        $providers = $this->availableProvidersForUser($user, $entitlements);
        $activeKey = (string) ($validated['active_provider_key'] ?? 'null');
        if (!array_key_exists($activeKey, $providers)) {
            return back()
                ->withErrors(['active_provider_key' => 'Bu sağlayıcı için lisansınız yok.'])
                ->withInput();
        }

        EInvoiceSetting::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'active_provider_key' => $activeKey,
                'auto_draft_enabled' => $validated['auto_draft_enabled'],
                'auto_issue_enabled' => $validated['auto_issue_enabled'],
                'draft_on_status' => $validated['draft_on_status'],
                'issue_on_status' => $validated['issue_on_status'],
                'prefix' => $validated['prefix'],
                'default_vat_rate' => $validated['default_vat_rate'],
            ]
        );

        if ($activeKey === 'custom') {
            if (empty($validated['custom_base_url']) || empty($validated['custom_api_key'])) {
                return back()
                    ->withErrors(['custom_base_url' => 'Custom provider için Base URL ve API Key gerekli.'])
                    ->withInput();
            }

            EInvoiceProviderInstallation::query()->updateOrCreate(
                ['user_id' => $user->id, 'provider_key' => 'custom'],
                [
                    'status' => 'active',
                    'credentials' => [
                        'base_url' => $validated['custom_base_url'],
                        'api_key' => $validated['custom_api_key'],
                    ],
                    'settings' => null,
                ]
            );
        }

        return back()->with('success', 'E-Fatura ayarları güncellendi.');
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function availableProvidersForUser($user, EntitlementService $entitlements): array
    {
        $all = (array) config('einvoice_providers.providers', []);
        $available = [];

        foreach ($all as $key => $meta) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            if ($key === 'null') {
                $available[$key] = $meta;
                continue;
            }
$moduleCode = "integration.einvoice.{$key}";
            if ($entitlements->hasModule($user, $moduleCode)) {
                $available[$key] = $meta;
            }
        }

        return $available;
    }
}


