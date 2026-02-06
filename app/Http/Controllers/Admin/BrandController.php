<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $brands = Brand::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->paginate(25);

        return view('admin.products.brands.index', compact('brands'));
    }

    public function create(): View
    {
        return view('admin.products.brands.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate(['name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name')->where('user_id', $user->id),
            ],
        ]);

        $brand = Brand::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
        ]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'id' => $brand->id,
                'name' => $brand->name,
            ], 201);
        }

        return redirect()->route('portal.brands.index')
            ->with('success', 'Marka oluşturuldu.');
    }

    public function edit(Brand $brand): View
    {
        $this->ensureOwner($brand);

        return view('admin.products.brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand): RedirectResponse|JsonResponse
    {
        $this->ensureOwner($brand);
        $user = $request->user();

        $validated = $request->validate(['name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name')->ignore($brand->id)->where('user_id', $user->id),
            ],
        ]);

        $brand->update(['name' => $validated['name'],
        ]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'id' => $brand->id,
                'name' => $brand->name,
            ]);
        }

        return redirect()->route('portal.brands.index')
            ->with('success', 'Marka güncellendi.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $this->ensureOwner($brand);
        $brand->delete();

        return redirect()->route('portal.brands.index')
            ->with('success', 'Marka silindi.');
    }

    private function ensureOwner(Brand $brand): void
    {
        $user = auth()->user();
        if (!$user || $brand->user_id !== $user->id) {
            abort(403);
        }
    }
}


