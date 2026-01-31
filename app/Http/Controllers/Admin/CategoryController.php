<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncMarketplaceCategoriesJob;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\CategoryMapping;
use App\Models\Marketplace;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceCredential;
use App\Services\Marketplace\Category\MarketplaceCategorySyncService;
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
        $search = trim((string) $request->query('q', ''));
        $openCategoryId = $request->query('open_category_id');
        $categoryImportOnlyLeafDefault = (bool) AppSetting::getValue('category_import_only_leaf_default', true);
        $categoryImportCreateMappingsDefault = (bool) AppSetting::getValue('category_import_create_mappings_default', true);

        $marketplaces = Marketplace::query()
            ->where('is_active', true)
            ->whereIn('id', function ($query) use ($user) {
                $query->select('marketplace_id')
                    ->from('marketplace_credentials')
                    ->where('user_id', $user->id)
                    ->where('is_active', true);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $categoriesQuery = Category::query()
            ->where('user_id', $user->id)
            ->orderBy('name');

        if ($search !== '') {
            $categoriesQuery->where('name', 'like', '%' . $search . '%');
        }

        $categories = $categoriesQuery->paginate(25)->withQueryString();

        $categoryIds = $categories->getCollection()->pluck('id');
        $mappingsByCategory = CategoryMapping::query()
            ->where('user_id', $user->id)
            ->whereIn('category_id', $categoryIds)
            ->with('marketplaceCategory')
            ->get()
            ->groupBy('category_id')
            ->map(fn ($rows) => $rows->keyBy('marketplace_id'));

        return view('admin.products.categories.index', compact(
            'categories',
            'marketplaces',
            'mappingsByCategory',
            'search',
            'openCategoryId',
            'categoryImportOnlyLeafDefault',
            'categoryImportCreateMappingsDefault'
        ));
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

    public function importFromMarketplace(Request $request, MarketplaceCategorySyncService $syncService): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'marketplace_id' => 'required|integer|exists:marketplaces,id',
            'only_leaf' => 'nullable|boolean',
            'create_mappings' => 'nullable|boolean',
        ]);

        $marketplace = Marketplace::query()->findOrFail($validated['marketplace_id']);
        $credential = MarketplaceCredential::query()
            ->where('user_id', $user->id)
            ->where('marketplace_id', $marketplace->id)
            ->where('is_active', true)
            ->firstOrFail();

        $onlyLeaf = $request->boolean('only_leaf', true);
        $createMappings = $request->boolean('create_mappings', true);

        $query = MarketplaceCategory::query()
            ->where('user_id', $user->id)
            ->where('marketplace_id', $marketplace->id);

        if ($onlyLeaf) {
            $query->where('is_leaf', true);
        }

        if ((clone $query)->count() === 0) {
            SyncMarketplaceCategoriesJob::dispatch($credential->id);

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Kategori senkronu kuyruğa alındı. Lütfen 1-2 dakika sonra tekrar deneyin.',
                ], 202);
            }

            return redirect()->route('admin.categories.index')
                ->with('info', 'Kategori senkronu kuyruğa alındı. Lütfen 1-2 dakika sonra tekrar deneyin.');
        }

        $items = $query->orderBy('path')->get(['id', 'external_id', 'path', 'name']);

        $created = 0;
        $mapped = 0;

        foreach ($items as $item) {
            $name = $item->path ?: $item->name;
            if (!$name) {
                continue;
            }

            $category = Category::query()->firstOrCreate(
                ['user_id' => $user->id, 'name' => $name],
                ['user_id' => $user->id, 'name' => $name]
            );

            if ($category->wasRecentlyCreated) {
                $created++;
            }

            if ($createMappings) {
                $mapping = CategoryMapping::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'category_id' => $category->id,
                        'marketplace_id' => $marketplace->id,
                    ],
                    [
                        'marketplace_category_id' => $item->id,
                        'marketplace_category_external_id' => $item->external_id,
                        'source' => 'import',
                        'confidence' => 100,
                    ]
                );
                if ($mapping->wasRecentlyCreated) {
                    $mapped++;
                }
            }
        }

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'created' => $created,
                'mapped' => $mapped,
            ]);
        }

        return redirect()->route('admin.categories.index')
            ->with('success', "İçe aktarma tamamlandı. Yeni kategori: {$created}, eşlenen: {$mapped}.");
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
