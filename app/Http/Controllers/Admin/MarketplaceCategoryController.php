<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncMarketplaceCategoriesJob;
use App\Models\Marketplace;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceCredential;
use App\Services\Marketplace\Category\MarketplaceCategorySyncService;
use App\Services\Marketplace\Category\UnsupportedMarketplaceCategoriesException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceCategoryController extends Controller
{
    public function sync(Request $request, Marketplace $marketplace, MarketplaceCategorySyncService $syncService): JsonResponse
    {
        $user = $request->user();

        $credential = MarketplaceCredential::query()
            ->where('user_id', $user->id)
            ->where('marketplace_id', $marketplace->id)
            ->where('is_active', true)
            ->first();

        if (!$credential) {
            return response()->json([
                'ok' => false,
                'message' => 'Bu pazaryeri baglantisi aktif degil.',
            ], 422);
        }

        try {
            SyncMarketplaceCategoriesJob::dispatch($credential->id);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Senkron isini kuyruga alamadik: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Kategori senkronu kuyruğa alındı. Birkaç dakika içinde güncellenecektir.',
        ], 202);
    }

    public function search(Request $request, Marketplace $marketplace): JsonResponse
    {
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['items' => []]);
        }

        $items = MarketplaceCategory::query()
            ->where('user_id', $user->id)
            ->where('marketplace_id', $marketplace->id)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('path', 'like', '%' . $q . '%');
            })
            ->orderBy('path')
            ->limit(30)
            ->get(['id', 'external_id', 'name', 'path', 'is_leaf']);

        return response()->json([
            'items' => $items,
        ]);
    }
}
