<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Mews\Purifier\Facades\Purifier;

class ProductController extends Controller
{
    private function ensureOwner(Product $product): void
    {
        $user = auth()->user();
        if ($user && !$user->isSuperAdmin() && $product->user_id !== $user->id) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Product::withCount('marketplaceProducts')
            ->with(['marketplaceProducts.marketplace' => function ($builder) {
                $builder->select('id', 'name', 'code', 'settings');
            }]);

        if ($user && !$user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('search')) {
            $term = $request->query('search');
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', '%'.$term.'%')
                    ->orWhere('sku', 'like', '%'.$term.'%')
                    ->orWhere('barcode', 'like', '%'.$term.'%')
                    ->orWhere('brand', 'like', '%'.$term.'%');
            });
        }

        $sortKey = $request->query('sort');
        $sortDir = $request->query('dir', 'asc');
        $allowedSorts = [
            'sku' => 'sku',
            'name' => 'name',
            'brand' => 'brand',
            'price' => 'price',
            'stock' => 'stock_quantity',
            'marketplace' => 'marketplace_products_count',
            'status' => 'is_active',
        ];
        if ($sortKey && isset($allowedSorts[$sortKey])) {
            $direction = $sortDir === 'desc' ? 'desc' : 'asc';
            $query->orderBy($allowedSorts[$sortKey], $direction);
        } else {
            $query->orderBy('name', 'asc');
        }

        $perPageParam = $request->query('per_page', 20);
        $allowed = [10, 20, 50, 100];
        $perPage = (int) $perPageParam;
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 20;
        }

        $products = $query->paginate($perPage)->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $query = Product::query()->latest();

        if ($user && !$user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        $filename = 'products-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'sku',
                'barcode',
                'name',
                'description',
                'brand',
                'category',
                'price',
                'cost_price',
                'stock_quantity',
                'currency',
                'weight',
                'desi',
                'vat_rate',
                'image_url',
                'is_active',
            ]);

            $query->chunk(200, function ($products) use ($handle) {
                foreach ($products as $product) {
                    fputcsv($handle, [
                        $product->sku,
                        $product->barcode,
                        $product->name,
                        $product->description,
                        $product->brand,
                        $product->category,
                        $product->price,
                        $product->cost_price,
                        $product->stock_quantity,
                        $product->currency,
                        $product->weight,
                        $product->desi,
                        $product->vat_rate,
                        $product->image_url,
                        $product->is_active ? 1 : 0,
                    ]);
                }
            });
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportTemplate()
    {
        $filename = 'products-template.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'sku',
                'barcode',
                'name',
                'description',
                'brand',
                'category',
                'price',
                'cost_price',
                'stock_quantity',
                'currency',
                'weight',
                'desi',
                'vat_rate',
                'image_url',
                'is_active',
            ]);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $user = $request->user();
        if ($user && !$user->isSuperAdmin()) {
            $subscription = $user->subscription;
            if (!$subscription || !$subscription->isActive()) {
                return back()->with('info', 'Ürün içe aktarmak için aktif abonelik gerekiyor.');
            }
        }

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return back()->with('info', 'Dosya okunamadı.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->with('info', 'CSV başlığı bulunamadı.');
        }

        $header = array_map(function ($value) {
            return Str::of($value)->trim()->lower()->value();
        }, $header);

        $required = ['sku', 'name', 'price', 'stock_quantity'];
        foreach ($required as $req) {
            if (!in_array($req, $header, true)) {
                fclose($handle);
                return back()->with('info', 'CSV başlığında zorunlu alan eksik: ' . $req);
            }
        }

        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === 0 || (count($row) === 1 && $row[0] === null)) {
                continue;
            }

            $data = array_combine($header, array_pad($row, count($header), null));
            if (!$data) {
                $skipped++;
                continue;
            }

            $sku = trim((string) ($data['sku'] ?? ''));
            $name = trim((string) ($data['name'] ?? ''));
            $price = $data['price'] ?? null;
            $stock = $data['stock_quantity'] ?? null;

            if ($sku === '' || $name === '' || $price === null || $stock === null) {
                $skipped++;
                continue;
            }

            if ($user && !$user->isSuperAdmin()) {
                if (!$user->subscription?->canAddProduct()) {
                    $skipped++;
                    continue;
                }
            }

            $exists = Product::where('sku', $sku)->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            $product = Product::create([
                'user_id' => ($user && !$user->isSuperAdmin()) ? $user->id : null,
                'sku' => $sku,
                'barcode' => $data['barcode'] ?? null,
                'name' => $name,
                'description' => $this->sanitizeDescription($data['description'] ?? null),
                'brand' => $data['brand'] ?? null,
                'category' => $data['category'] ?? null,
                'price' => (float) $price,
                'cost_price' => isset($data['cost_price']) && $data['cost_price'] !== '' ? (float) $data['cost_price'] : null,
                'stock_quantity' => (int) $stock,
                'currency' => $data['currency'] ?? 'TRY',
                'weight' => isset($data['weight']) && $data['weight'] !== '' ? (float) $data['weight'] : null,
                'desi' => isset($data['desi']) && $data['desi'] !== '' ? (float) $data['desi'] : null,
                'vat_rate' => isset($data['vat_rate']) && $data['vat_rate'] !== '' ? (int) $data['vat_rate'] : null,
                'image_url' => $data['image_url'] ?? null,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            ]);

            if ($product && $user && !$user->isSuperAdmin()) {
                $user->subscription?->incrementProducts();
            }

            $imported++;
        }

        fclose($handle);

        return back()->with('success', "İçe aktarma tamamlandı. Başarılı: {$imported}, Atlanan: {$skipped}");
    }

    public function create()
    {
        $user = auth()->user();
        $categories = Category::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();
        $brands = Brand::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        return view('admin.products.create', compact('categories', 'brands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sku' => 'required|string|unique:products,sku',
            'barcode' => 'nullable|string',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'brand' => 'nullable|string',
            'category' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'desi' => 'nullable|numeric|min:0',
            'vat_rate' => 'nullable|integer|in:0,1,10,20',
            'images.*' => 'nullable|image|max:5120',
            'is_active' => 'boolean',
        ]);

        $validated['description'] = $this->sanitizeDescription($validated['description'] ?? null);

        $user = $request->user();
        if ($user && !$user->isSuperAdmin()) {
            $subscription = $user->subscription;
            if (!$subscription || !$subscription->isActive() || !$subscription->canAddProduct()) {
                return back()->with('info', 'Abonelik limitiniz doldu. Daha fazla ürün ekleyemezsiniz.');
            }
            $validated['user_id'] = $user->id;
        }

        $uploadedImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                if ($image && $image->isValid()) {
                    $uploadedImages[] = $image->store('products', 'public');
                }
            }
        }

        if (!empty($uploadedImages)) {
            $validated['images'] = $uploadedImages;
            $validated['image_url'] = $uploadedImages[0];
        }

        $product = Product::create($validated);

        if ($user && !$user->isSuperAdmin()) {
            $user->subscription?->incrementProducts();
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Ürün başarıyla oluşturuldu.');
    }

    public function show(Product $product)
    {
        $this->ensureOwner($product);
        $product->load('marketplaceProducts.marketplace');

        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            $availableMarketplaces = \App\Models\Marketplace::where('is_active', true)->get();
        } else {
            $availableMarketplaces = \App\Models\Marketplace::whereIn('id', function ($query) use ($user) {
                $query->select('marketplace_id')
                    ->from('marketplace_credentials')
                    ->where('user_id', $user->id)
                    ->where('is_active', true);
            })->where('is_active', true)->get();
        }

        return view('admin.products.show', compact('product', 'availableMarketplaces'));
    }

    public function edit(Product $product)
    {
        $this->ensureOwner($product);
        $user = auth()->user();
        $categories = Category::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();
        $brands = Brand::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        return view('admin.products.edit', compact('product', 'categories', 'brands'));
    }

    public function update(Request $request, Product $product)
    {
        $this->ensureOwner($product);
        $validated = $request->validate([
            'sku' => 'required|string|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'brand' => 'nullable|string',
            'category' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'desi' => 'nullable|numeric|min:0',
            'vat_rate' => 'nullable|integer|in:0,1,10,20',
            'images.*' => 'nullable|image|max:5120',
            'is_active' => 'boolean',
        ]);

        $validated['description'] = $this->sanitizeDescription($validated['description'] ?? null);

        $uploadedImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                if ($image && $image->isValid()) {
                    $uploadedImages[] = $image->store('products', 'public');
                }
            }
        }

        if (!empty($uploadedImages)) {
            $existingImages = is_array($product->images) ? $product->images : [];
            $mergedImages = array_values(array_unique(array_merge($existingImages, $uploadedImages)));
            $validated['images'] = $mergedImages;
            if (!$product->image_url) {
                $validated['image_url'] = $mergedImages[0];
            }
        }

        $product->update($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Ürün başarıyla güncellendi.');
    }

    public function quickUpdate(Request $request, Product $product)
    {
        $this->ensureOwner($product);

        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        $product->update([
            'price' => $validated['price'],
            'stock_quantity' => $validated['stock_quantity'],
        ]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'id' => $product->id,
                'price' => $product->price,
                'stock_quantity' => $product->stock_quantity,
            ]);
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Ürün güncellendi.');
    }

    public function destroy(Product $product)
    {
        $this->ensureOwner($product);
        $product->delete();

        $user = auth()->user();
        if ($user && !$user->isSuperAdmin()) {
            $user->subscription?->decrementProducts();
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Ürün başarıyla silindi.');
    }
    private function sanitizeDescription(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        return Purifier::clean($html, 'product_description');
    }
}
