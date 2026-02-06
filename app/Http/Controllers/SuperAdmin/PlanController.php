<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlanController extends Controller
{
    private function entitlementModuleGroups(): array
    {
        $modules = Module::query()
            ->where('is_active', true)
            ->whereIn('type', ['feature', 'integration'])
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['code', 'name', 'type']);

        $featureItems = [];
        $integrationItems = [];

        foreach ($modules as $module) {
            if (!is_string($module->code) || trim($module->code) === '') {
                continue;
            }
$label = is_string($module->name) && trim($module->name) !== '' ? $module->name : $module->code;

            if ($module->type === 'integration') {
                $integrationItems[$module->code] = $label;
            } else {
                $featureItems[$module->code] = $label;
            }
        }

        return [
            [
                'key' => 'feature',
                'label' => 'Feature Modülleri',
                'items' => $featureItems,
            ],
            [
                'key' => 'integration',
                'label' => 'Integration Modülleri',
                'items' => $integrationItems,
            ],
        ];
    }

    /**
     * @return array<int,string>
     */
    private function entitlementModuleCodes(): array
    {
        return Module::query()
            ->where('is_active', true)
            ->whereIn('type', ['feature', 'integration'])
            ->orderBy('code')
            ->pluck('code')
            ->filter(fn ($c) => is_string($c) && trim($c) !== '')
            ->values()
            ->all();
    }

    public function index()
    {
        $plans = Plan::orderBy('sort_order')->orderBy('price')->get();

        return view('super-admin.plans.index', compact('plans'));
    }

    public function create()
    {
        $entitlementModuleGroups = $this->entitlementModuleGroups();
        $selectedModules = [];

        return view('super-admin.plans.create', compact('entitlementModuleGroups', 'selectedModules'));
    }

    public function store(Request $request)
    {
        $entitlementCodes = $this->entitlementModuleCodes();

        $validated = $request->validate(['name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'yearly_price' => 'nullable|numeric|min:0',
            'billing_period' => 'required|in:monthly,yearly',
            'max_products' => 'required|integer|min:0',
            'max_marketplaces' => 'required|integer|min:0',
            'max_orders_per_month' => 'required|integer|min:0',
            'max_tickets_per_month' => 'required|integer|min:0',
            'api_access' => 'boolean',
            'advanced_reports' => 'boolean',
            'priority_support' => 'boolean',
            'custom_integrations' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'modules' => 'nullable|array',
            'modules.*' => ['string', Rule::in($entitlementCodes)],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $this->assertSlugUnique($validated['slug']);
        $validated['api_access'] = $request->boolean('api_access');
        $validated['advanced_reports'] = $request->boolean('advanced_reports');
        $validated['priority_support'] = $request->boolean('priority_support');
        $validated['custom_integrations'] = $request->boolean('custom_integrations');
        $validated['is_active'] = $request->boolean('is_active');

        $modules = $validated['modules'] ?? [];
        unset($validated['modules']);

        $plan = new Plan();
        $validated['features'] = $plan->withModules($modules);

        Plan::create($validated);

        return redirect()->route('super-admin.plans.index')
            ->with('success', 'Paket başarıyla oluşturuldu.');
    }

    public function edit(Plan $plan)
    {
        $entitlementModuleGroups = $this->entitlementModuleGroups();
        $selectedModules = $plan->enabledModules();

        return view('super-admin.plans.edit', compact('plan', 'entitlementModuleGroups', 'selectedModules'));
    }

    public function update(Request $request, Plan $plan)
    {
        $entitlementCodes = $this->entitlementModuleCodes();

        $validated = $request->validate(['name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'yearly_price' => 'nullable|numeric|min:0',
            'billing_period' => 'required|in:monthly,yearly',
            'max_products' => 'required|integer|min:0',
            'max_marketplaces' => 'required|integer|min:0',
            'max_orders_per_month' => 'required|integer|min:0',
            'max_tickets_per_month' => 'required|integer|min:0',
            'api_access' => 'boolean',
            'advanced_reports' => 'boolean',
            'priority_support' => 'boolean',
            'custom_integrations' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'modules' => 'nullable|array',
            'modules.*' => ['string', Rule::in($entitlementCodes)],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $this->assertSlugUnique($validated['slug'], $plan);
        $validated['api_access'] = $request->boolean('api_access');
        $validated['advanced_reports'] = $request->boolean('advanced_reports');
        $validated['priority_support'] = $request->boolean('priority_support');
        $validated['custom_integrations'] = $request->boolean('custom_integrations');
        $validated['is_active'] = $request->boolean('is_active');

        $modules = $validated['modules'] ?? [];
        unset($validated['modules']);

        $validated['features'] = $plan->withModules($modules);

        $plan->update($validated);

        return redirect()->route('super-admin.plans.index')
            ->with('success', 'Paket başarıyla güncellendi.');
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();

        return redirect()->route('super-admin.plans.index')
            ->with('success', 'Paket başarıyla silindi.');
    }

    private function assertSlugUnique(string $slug, ?Plan $current = null): void
    {
        $slug = trim($slug);
        if ($slug === '') {
            throw ValidationException::withMessages([
                'name' => 'Paket adı geçersiz.',
            ]);
        }
$query = Plan::query()->where('slug', $slug);
        if ($current) {
            $query->where('id', '!=', $current->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Bu paket adı zaten kullanılıyor.',
            ]);
        }
    }
}

