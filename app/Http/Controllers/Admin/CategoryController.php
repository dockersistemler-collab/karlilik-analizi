<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $categories = Category::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->paginate(25);

        return view('admin.products.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.products.categories.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where('user_id', $user->id),
            ],
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
        ]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'id' => $category->id,
                'name' => $category->name,
            ], 201);
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori oluşturuldu.');
    }

    public function edit(Category $category): View
    {
        $this->ensureOwner($category);

        return view('admin.products.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse|JsonResponse
    {
        $this->ensureOwner($category);
        $user = $request->user();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($category->id)->where('user_id', $user->id),
            ],
        ]);

        $category->update([
            'name' => $validated['name'],
        ]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'id' => $category->id,
                'name' => $category->name,
            ]);
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori güncellendi.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->ensureOwner($category);
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori silindi.');
    }

    private function ensureOwner(Category $category): void
    {
        $user = auth()->user();
        if (!$user || $category->user_id !== $user->id) {
            abort(403);
        }
    }
}
