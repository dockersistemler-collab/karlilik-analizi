<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    private function moduleGroups(): array
    {
        $marketplaces = Marketplace::query()
            ->orderBy('name')
            ->get(['name', 'code', 'is_active']);

        $marketplaceItems = [];
        foreach ($marketplaces as $marketplace) {
            if (!$marketplace->code) {
                continue;
            }
            $marketplaceItems['integrations.marketplace.' . $marketplace->code] = $marketplace->name;
        }

        return [
            [
                'key' => 'core',
                'label' => 'Genel Modüller',
                'items' => Plan::MODULES,
            ],
            [
                'key' => 'reports',
                'label' => 'Raporlar',
                'items' => Plan::REPORT_MODULES,
            ],
            [
                'key' => 'exports',
                'label' => 'Exportlar',
                'items' => Plan::EXPORT_MODULES,
            ],
            [
                'key' => 'integrations',
                'label' => 'Entegrasyon Pazaryerleri',
                'items' => $marketplaceItems,
            ],
        ];
    }

    private function moduleOptionsFlat(): array
    {
        $flat = [];
        foreach ($this->moduleGroups() as $group) {
            foreach (($group['items'] ?? []) as $key => $label) {
                $flat[$key] = $label;
            }
        }
        ksort($flat);

        return $flat;
    }

    public function index()
    {
        $plans = Plan::orderBy('sort_order')->orderBy('price')->get();

        return view('super-admin.plans.index', compact('plans'));
    }

    public function create()
    {
        $moduleGroups = $this->moduleGroups();
        $selectedModules = array_keys($this->moduleOptionsFlat());

        return view('super-admin.plans.create', compact('moduleGroups', 'selectedModules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
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
            'modules.*' => ['string', Rule::in(array_keys($this->moduleOptionsFlat()))],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['api_access'] = $request->boolean('api_access');
        $validated['advanced_reports'] = $request->boolean('advanced_reports');
        $validated['priority_support'] = $request->boolean('priority_support');
        $validated['custom_integrations'] = $request->boolean('custom_integrations');
        $validated['is_active'] = $request->boolean('is_active');

        $modules = $validated['modules'] ?? array_keys($this->moduleOptionsFlat());
        unset($validated['modules']);

        $plan = new Plan();
        $validated['features'] = $plan->withModules($modules);

        Plan::create($validated);

        return redirect()->route('super-admin.plans.index')
            ->with('success', 'Paket başarıyla oluşturuldu.');
    }

    public function edit(Plan $plan)
    {
        $moduleGroups = $this->moduleGroups();
        $selectedModules = $plan->enabledModules() ?? array_keys($this->moduleOptionsFlat());

        return view('super-admin.plans.edit', compact('plan', 'moduleGroups', 'selectedModules'));
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
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
            'modules.*' => ['string', Rule::in(array_keys($this->moduleOptionsFlat()))],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['api_access'] = $request->boolean('api_access');
        $validated['advanced_reports'] = $request->boolean('advanced_reports');
        $validated['priority_support'] = $request->boolean('priority_support');
        $validated['custom_integrations'] = $request->boolean('custom_integrations');
        $validated['is_active'] = $request->boolean('is_active');

        $modules = $validated['modules'] ?? array_keys($this->moduleOptionsFlat());
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
}
