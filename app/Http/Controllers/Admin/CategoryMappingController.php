<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryMapping;
use App\Models\Marketplace;
use App\Models\MarketplaceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class CategoryMappingController extends Controller
{
    public function status(Request $request, Category $category): JsonResponse
    {
        $user = $request->user();
        if ($category->user_id !== $user->id) {
            abort(403);
        }
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

        $mappings = CategoryMapping::query()
            ->where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->with('marketplaceCategory')
            ->get()
            ->keyBy('marketplace_id');

        $payload = [];
        foreach ($marketplaces as $marketplace) {
            $mapping = $mappings->get($marketplace->id);
            $path = $mapping?->marketplaceCategory?->path;
$payload[] = [
                'marketplace_id' => $marketplace->id,
                'marketplace_name' => $marketplace->name,
                'marketplace_code' => $marketplace->code,
                'is_mapped' => (bool) $mapping,
                'mapped_external_id' => $mapping?->marketplace_category_external_id, 'mapped_path' => $path,
            ];
        }

        return response()->json([
            'ok' => true,
            'category_id' => $category->id,
            'items' => $payload,
            'manage_url' => route('portal.categories.index', ['open_category_id' => $category->id]),
        ]);
    }

    public function upsert(Request $request, Category $category, Marketplace $marketplace): JsonResponse
    {
        $user = $request->user();
        if ($category->user_id !== $user->id) {
            abort(403);
        }
$validated = $request->validate(['external_id' => 'required|string|max:255',
        ]);

        $marketplaceCategory = MarketplaceCategory::query()
            ->where('user_id', $user->id)
            ->where('marketplace_id', $marketplace->id)
            ->where('external_id', $validated['external_id'])
            ->first();

        if (!$marketplaceCategory) {
            return response()->json([
                'ok' => false,
                'message' => 'Secilen kategori cache icinde bulunamadi. Once senkron yapin.',
            ], 422);
        }

        try {
            $mapping = CategoryMapping::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'category_id' => $category->id,
                    'marketplace_id' => $marketplace->id,
                ],
                [
                    'marketplace_category_id' => $marketplaceCategory->id,
                    'marketplace_category_external_id' => $marketplaceCategory->external_id,
                    'source' => 'manual',
                    'confidence' => null,
                ]
            );
        } catch (QueryException $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Bu pazaryeri kategorisi zaten baska bir kategorinize eslenmis olabilir.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'mapping' => [
                'id' => $mapping->id,
                'external_id' => $mapping->marketplace_category_external_id,
                'path' => $marketplaceCategory->path ?: $marketplaceCategory->name,
            ],
        ]);
    }

    public function destroy(Request $request, Category $category, Marketplace $marketplace): JsonResponse
    {
        $user = $request->user();
        if ($category->user_id !== $user->id) {
            abort(403);
        }

        CategoryMapping::query()
            ->where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->where('marketplace_id', $marketplace->id)
            ->delete();

        return response()->json([
            'ok' => true,
        ]);
    }
}


