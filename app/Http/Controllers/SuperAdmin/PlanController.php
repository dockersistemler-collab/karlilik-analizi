<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::orderBy('sort_order')->orderBy('price')->get();

        return view('super-admin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('super-admin.plans.create');
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
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['api_access'] = $request->boolean('api_access');
        $validated['advanced_reports'] = $request->boolean('advanced_reports');
        $validated['priority_support'] = $request->boolean('priority_support');
        $validated['custom_integrations'] = $request->boolean('custom_integrations');
        $validated['is_active'] = $request->boolean('is_active');

        Plan::create($validated);

        return redirect()->route('super-admin.plans.index')
            ->with('success', 'Paket başarıyla oluşturuldu.');
    }

    public function edit(Plan $plan)
    {
        return view('super-admin.plans.edit', compact('plan'));
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
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['api_access'] = $request->boolean('api_access');
        $validated['advanced_reports'] = $request->boolean('advanced_reports');
        $validated['priority_support'] = $request->boolean('priority_support');
        $validated['custom_integrations'] = $request->boolean('custom_integrations');
        $validated['is_active'] = $request->boolean('is_active');

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
