<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ReferralProgram;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $program = ReferralProgram::query()->latest()->first();

        return view('super-admin.settings.index', compact('program'));
    }

    public function updateReferralProgram(Request $request)
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
}
