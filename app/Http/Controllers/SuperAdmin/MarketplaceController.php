<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MarketplaceController extends Controller
{
    public function index()
    {
        $marketplaces = Marketplace::latest()->paginate(20);

        return view('super-admin.marketplaces.index', compact('marketplaces'));
    }

    public function create()
    {
        return view('super-admin.marketplaces.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255',
            'code' => 'required|string|unique:marketplaces,code',
            'api_url' => 'nullable|url',
            'logo_url' => 'nullable|string|max:2048',
            'logo_file' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $settings = [];
        if ($request->filled('logo_url')) {
            $settings['logo_url'] = trim((string) $request->input('logo_url'));
        }
        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('marketplace-logos', 'public');
            $settings['logo_path'] = $path;
            $settings['logo_url'] = '/storage/' . ltrim($path, '/');
        }
$validated['settings'] = empty($settings) ? null : $settings;

        Marketplace::create($validated);

        return redirect()->route('super-admin.marketplaces.index')
            ->with('success', 'Pazaryeri başarıyla oluşturuldu.');
    }

    public function show(Marketplace $marketplace)
    {
        return view('super-admin.marketplaces.show', compact('marketplace'));
    }

    public function edit(Marketplace $marketplace)
    {
        return view('super-admin.marketplaces.edit', compact('marketplace'));
    }

    public function update(Request $request, Marketplace $marketplace)
    {
        $validated = $request->validate(['name' => 'required|string|max:255',
            'code' => 'required|string|unique:marketplaces,code,' . $marketplace->id,
            'api_url' => 'nullable|url',
            'logo_url' => 'nullable|string|max:2048',
            'logo_file' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $settings = $marketplace->settings ?? [];
        if ($request->has('logo_url')) {
            $logoUrl = trim((string) $request->input('logo_url'));
            if ($logoUrl === '') {
                if (!empty($settings['logo_path']) && Storage::disk('public')->exists($settings['logo_path'])) {
                    Storage::disk('public')->delete($settings['logo_path']);
                }
                unset($settings['logo_url'], $settings['logo_path']);
            } else {
                if (!empty($settings['logo_path']) && Storage::disk('public')->exists($settings['logo_path'])) {
                    Storage::disk('public')->delete($settings['logo_path']);
                }
                unset($settings['logo_path']);
                $settings['logo_url'] = $logoUrl;
            }
        }
        if ($request->hasFile('logo_file')) {
            if (!empty($settings['logo_path']) && Storage::disk('public')->exists($settings['logo_path'])) {
                Storage::disk('public')->delete($settings['logo_path']);
            }
$path = $request->file('logo_file')->store('marketplace-logos', 'public');
            $settings['logo_path'] = $path;
            $settings['logo_url'] = '/storage/' . ltrim($path, '/');
        }
$validated['settings'] = empty($settings) ? null : $settings;

        $marketplace->update($validated);

        return redirect()->route('super-admin.marketplaces.index')
            ->with('success', 'Pazaryeri başarıyla güncellendi.');
    }

    public function destroy(Marketplace $marketplace)
    {
        $marketplace->delete();

        return redirect()->route('super-admin.marketplaces.index')
            ->with('success', 'Pazaryeri başarıyla silindi.');
    }
}
