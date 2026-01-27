<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(): View
    {
        $banners = Banner::query()
            ->orderBy('placement')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('super-admin.banners.index', compact('banners'));
    }

    public function create(): View
    {
        $placements = $this->placements();

        return view('super-admin.banners.create', compact('placements'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateBanner($request);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('banners', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['show_countdown'] = $request->boolean('show_countdown');

        Banner::create($validated);

        return redirect()->route('super-admin.banners.index')
            ->with('success', 'Banner eklendi.');
    }

    public function edit(Banner $banner): View
    {
        $placements = $this->placements();

        return view('super-admin.banners.edit', compact('banner', 'placements'));
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $validated = $this->validateBanner($request);

        if ($request->hasFile('image')) {
            if ($banner->image_path) {
                Storage::disk('public')->delete($banner->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('banners', 'public');
        }

        if ($request->boolean('remove_image') && $banner->image_path) {
            Storage::disk('public')->delete($banner->image_path);
            $validated['image_path'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['show_countdown'] = $request->boolean('show_countdown');

        $banner->update($validated);

        return redirect()->route('super-admin.banners.index')
            ->with('success', 'Banner güncellendi.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        if ($banner->image_path) {
            Storage::disk('public')->delete($banner->image_path);
        }

        $banner->delete();

        return redirect()->route('super-admin.banners.index')
            ->with('success', 'Banner silindi.');
    }

    private function placements(): array
    {
        return [
            'admin_header' => 'Müşteri Paneli Üst Bar',
            'public_header' => 'Public Site Üst Bar',
        ];
    }

    private function validateBanner(Request $request): array
    {
        return $request->validate([
            'placement' => 'required|string|max:50',
            'title' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:1000',
            'link_url' => 'nullable|url|max:500',
            'link_text' => 'nullable|string|max:255',
            'bg_color' => 'nullable|string|max:20',
            'text_color' => 'nullable|string|max:20',
            'image' => 'nullable|image|max:4096',
            'is_active' => 'nullable|boolean',
            'show_countdown' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at|required_if:show_countdown,1',
            'sort_order' => 'nullable|integer|min:0',
            'remove_image' => 'nullable|boolean',
        ]);
    }
}
