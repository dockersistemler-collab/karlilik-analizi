<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        return view('admin.settings', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
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

        $validated['invoice_number_tracking'] = $request->boolean('invoice_number_tracking');

        $user->update($validated);

        return back()->with('success', 'Genel ayarlar güncellendi.');
    }
}
