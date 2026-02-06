<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SupportUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $user = SupportUser::currentUser();

        $allowedTabs = [
            'company',
            'invoice',
            'product_list',
            'marketplaces',
            'shipping_label',
            'invoice_description_fields',
            'notifications',
            'products',
        ];

        $activeTab = (string) $request->query('tab', 'company');
        if (!in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'company';
        }

        return view('admin.settings', compact('user', 'activeTab'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = SupportUser::currentUser();

        $validated = $request->validate(['billing_name' => 'nullable|string|max:255',
            'billing_email' => 'nullable|email|max:255',
            'billing_address' => 'nullable|string|max:2000',
            'company_name' => 'nullable|string|max:255',
            'company_slogan' => 'nullable|string|max:255',
            'company_phone' => 'nullable|string|max:50',
            'notification_email' => 'nullable|email|max:255',
            'company_address' => 'nullable|string|max:2000',
            'company_website' => 'nullable|url|max:255',
            'invoice_number_tracking' => 'nullable|boolean',
            'company_logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('company_logo')) {
            if ($user->company_logo_path) {
                Storage::disk('public')->delete($user->company_logo_path);
            }
$path = $request->file('company_logo')->store('company-logos', 'public');
            $validated['company_logo_path'] = $path;
        }

        if ($request->has('invoice_number_tracking')) {
            $validated['invoice_number_tracking'] = $request->boolean('invoice_number_tracking');
        }
$user->update($validated);

        return back()->with('success', 'Genel ayarlar güncellendi.');
    }
}
