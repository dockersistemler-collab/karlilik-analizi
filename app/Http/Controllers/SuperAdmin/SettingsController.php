<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ReferralProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $program = ReferralProgram::query()->latest()->first();
        $users = User::query()
            ->where('role', 'client')
            ->orderBy('name')
            ->get();
        $selectedUserId = $request->query('user_id');
        $selectedUser = null;

        if ($selectedUserId) {
            $selectedUser = User::query()
                ->where('role', 'client')
                ->whereKey($selectedUserId)
                ->first();
        }

        if (!$selectedUser) {
            $selectedUser = $users->first();
        }

        return view('super-admin.settings.index', compact('program', 'users', 'selectedUser'));
    }

    public function updateReferralProgram(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'referrer_reward_type' => 'required|in:percent,duration',
            'referrer_reward_value' => 'nullable|numeric|min:0',
            'referred_reward_type' => 'required|in:percent,duration',
            'referred_reward_value' => 'nullable|numeric|min:0',
            'max_uses_per_referrer_per_year' => 'required|integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        ReferralProgram::query()->where('is_active', true)->update(['is_active' => false]);

        ReferralProgram::create($validated);

        return redirect()->route('super-admin.settings.index')
            ->with('success', 'Tavsiye programı güncellendi.');
    }

    public function updateClientSettings(Request $request, User $user): RedirectResponse
    {
        if (!$user->isClient()) {
            return redirect()->route('super-admin.settings.index')
                ->with('info', 'Sadece müşteri hesapları güncellenebilir.');
        }

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

        return redirect()->route('super-admin.settings.index', ['user_id' => $user->id])
            ->with('success', 'Müşteri ayarları güncellendi.');
    }
}
