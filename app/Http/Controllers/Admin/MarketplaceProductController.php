<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\MarketplaceProduct;
use Illuminate\Http\Request;

class MarketplaceProductController extends Controller
{
    /**
     * Ürünü pazaryerine ekle
     */
    public function assign(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'marketplace_id' => 'required|exists:marketplaces,id',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $this->ensureOwner($product);

        $user = $request->user();
        if ($user && !$user->isSuperAdmin()) {
            $hasActiveCredential = \App\Models\MarketplaceCredential::where('user_id', $user->id)
                ->where('marketplace_id', $validated['marketplace_id'])
                ->where('is_active', true)
                ->exists();

            if (!$hasActiveCredential) {
                return back()->with('info', 'Bu pazaryeri için aktif bağlantınız yok.');
            }
        }

        $exists = MarketplaceProduct::where('product_id', $validated['product_id'])
            ->where('marketplace_id', $validated['marketplace_id'])
            ->exists();

        if ($exists) {
            return back()->with('info', 'Bu ürün zaten seçilen pazaryerine eklenmiş.');
        }

        $marketplaceProduct = MarketplaceProduct::create($validated);

        return back()->with('success', 'Ürün pazaryerine başarıyla eklendi.');
    }

    /**
     * Pazaryeri ürününü güncelle
     */
    public function update(Request $request, MarketplaceProduct $marketplaceProduct)
    {
        $this->ensureOwner($marketplaceProduct->product);

        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'status' => 'required|in:draft,active,inactive,rejected',
        ]);

        $marketplaceProduct->update($validated);

        return back()->with('success', 'Pazaryeri ürünü başarıyla güncellendi.');
    }

    /**
     * Pazaryeri ürününü sil
     */
    public function destroy(MarketplaceProduct $marketplaceProduct)
    {
        $this->ensureOwner($marketplaceProduct->product);

        $marketplaceProduct->delete();

        return back()->with('success', 'Ürün pazaryerinden başarıyla kaldırıldı.');
    }

    /**
     * Pazaryerine ürün gönder
     */
    public function sync(MarketplaceProduct $marketplaceProduct)
    {
        $this->ensureOwner($marketplaceProduct->product);
        return back()->with('info', 'Senkronizasyon özelliği yakında eklenecek.');
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'marketplace_product_ids' => 'required|array',
            'marketplace_product_ids.*' => 'exists:marketplace_products,id',
            'price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'status' => 'nullable|in:draft,active,inactive,rejected',
        ]);

        if ($validated['price'] === null && $validated['stock_quantity'] === null && empty($validated['status'])) {
            return back()->with('info', 'Güncellemek için en az bir alan seçin.');
        }

        $items = MarketplaceProduct::whereIn('id', $validated['marketplace_product_ids'])->get();

        foreach ($items as $item) {
            $this->ensureOwner($item->product);
            $payload = [];
            if ($validated['price'] !== null) {
                $payload['price'] = $validated['price'];
            }
            if ($validated['stock_quantity'] !== null) {
                $payload['stock_quantity'] = $validated['stock_quantity'];
            }
            if (!empty($validated['status'])) {
                $payload['status'] = $validated['status'];
            }
            if (!empty($payload)) {
                $item->update($payload);
            }
        }

        return back()->with('success', 'Seçili pazaryeri ürünleri güncellendi.');
    }

    public function bulkSync(Request $request)
    {
        $validated = $request->validate([
            'marketplace_product_ids' => 'required|string',
        ]);

        $ids = collect(explode(',', $validated['marketplace_product_ids']))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($ids->isEmpty()) {
            return back()->with('info', 'Senkronize edilecek ürün seçilmedi.');
        }

        $items = MarketplaceProduct::whereIn('id', $ids)->get();

        foreach ($items as $item) {
            $this->ensureOwner($item->product);
            $item->update([
                'last_sync_at' => now(),
            ]);
        }

        return back()->with('success', 'Seçili ürünler senkronize edildi.');
    }

    private function ensureOwner(?Product $product): void
    {
        $user = auth()->user();
        if ($product && $user && !$user->isSuperAdmin() && $product->user_id !== $user->id) {
            abort(403);
        }
    }
}
