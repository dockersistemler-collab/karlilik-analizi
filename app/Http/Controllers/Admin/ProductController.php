<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Events\QuotaExceeded;
use App\Events\QuotaWarningReached;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Mews\Purifier\Facades\Purifier;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
            'cost' => 'cost_price',
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
$filename = 'products-' . now()->format('Ymd-His') . '.xlsx';
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Stok Kodu',
            'Barkod',
            'Ürün Adı',
            'Açıklama',
            'Marka',
            'Kategori',
            'Satış Fiyatı',
            'Alış Maliyeti',
            'Stok',
            'Para Birimi',
            'Ağırlık',
            'Desi',
            'KDV Oranı',
            'Görsel URL',
            'Aktif',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $rowIndex = 2;

        $query->chunk(200, function ($products) use ($sheet, &$rowIndex) {
            foreach ($products as $product) {
                $sheet->fromArray([$product->sku,
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
                ], null, 'A' . $rowIndex);
                $rowIndex++;
            }
        });

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'products_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    public function exportTemplate()
    {
        $filename = 'products-template.xlsx';
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Stok Kodu',
            'Barkod',
            'Ürün Adı',
            'Açıklama',
            'Marka',
            'Kategori',
            'Satış Fiyatı',
            'Alış Maliyeti',
            'Stok',
            'Para Birimi',
            'Ağırlık',
            'Desi',
            'KDV Oranı',
            'Görsel URL',
            'Aktif',
        ], null, 'A1');

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'products_template_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx',
        ]);

        $user = $request->user();
        if ($user && !$user->isSuperAdmin()) {
            $subscription = $user->subscription;
            if (!$subscription || !$subscription->isActive()) {
                return back()->with('info', 'Ürün içe aktarmak için aktif abonelik gerekiyor.');
            }
        }
$file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            return back()->with('info', 'Excel başlığı bulunamadı.');
        }
$header = array_shift($rows);
        $header = array_map(function ($value) {
            $normalized = Str::of((string) $value)->trim()->lower()->value();
            $normalized = strtr($normalized, [
                'ç' => 'c',
                'ğ' => 'g',
                'ı' => 'i',
                'ö' => 'o',
                'ş' => 's',
                'ü' => 'u',
            ]);
            $normalized = preg_replace('/\s+/', '_', $normalized);
            return $normalized;
        }, $header);

        $aliases = [
            'stok_kodu' => 'sku',
            'sku' => 'sku',
            'barkod' => 'barcode',
            'barcode' => 'barcode',
            'urun_adi' => 'name',
            'name' => 'name',
            'aciklama' => 'description',
            'description' => 'description',
            'marka' => 'brand',
            'brand' => 'brand',
            'kategori' => 'category',
            'category' => 'category',
            'satis_fiyati' => 'price',
            'price' => 'price',
            'alis_maliyeti' => 'cost_price',
            'cost_price' => 'cost_price',
            'stok' => 'stock_quantity',
            'stock_quantity' => 'stock_quantity',
            'para_birimi' => 'currency',
            'currency' => 'currency',
            'agirlik' => 'weight',
            'weight' => 'weight',
            'desi' => 'desi',
            'kdv_orani' => 'vat_rate',
            'vat_rate' => 'vat_rate',
            'gorsel_url' => 'image_url',
            'image_url' => 'image_url',
            'aktif' => 'is_active',
            'is_active' => 'is_active',
        ];

        $mappedHeader = [];
        foreach ($header as $col) {
            $mappedHeader[] = $aliases[$col] ?? $col;
        }
$header = $mappedHeader;

        $required = ['sku', 'name', 'price', 'stock_quantity'];
        foreach ($required as $req) {
            if (!in_array($req, $header, true)) {
                return back()->with('info', 'Excel başlığında zorunlu alan eksik: ' . $req);
            }
        }
$imported = 0;
        $skippedEmpty = 0;
        $skippedInvalid = 0;
        $skippedMissing = 0;
        $skippedLimit = 0;
        $skippedDuplicate = 0;

        foreach ($rows as $row) {
            if (count($row) === 0 || (count($row) === 1 && $row[0] === null)) {
                $skippedEmpty++;
                continue;
            }
$data = array_combine($header, array_pad($row, count($header), null));
            if (!$data) {
                $skippedInvalid++;
                continue;
            }
$sku = trim((string) ($data['sku'] ?? ''));
            $name = trim((string) ($data['name'] ?? ''));
            $price = $data['price'] ?? null;
            $stock = $data['stock_quantity'] ?? null;

            if ($name === '' || $price === null || $stock === null) {
                $skippedMissing++;
                continue;
            }
            if ($sku === '') {
                $sku = $this->generateSku($user);
            }

            if ($user && !$user->isSuperAdmin()) {
                if (!$user->subscription?->canAddProduct()) {
                    $skippedLimit++;
                    continue;
                }
            }
$existsQuery = Product::where('sku', $sku);
            if ($user && !$user->isSuperAdmin()) {
                $existsQuery->where('user_id', $user->id);
            }
$exists = $existsQuery->exists();
            if ($exists) {
                $skippedDuplicate++;
                continue;
            }
$categoryName = isset($data['category']) ? trim((string) $data['category']) : '';
            $categoryId = null;
            if ($categoryName !== '' && $user && !$user->isSuperAdmin()) {
                $categoryId = \App\Models\Category::query()
                    ->where('user_id', $user->id)
                    ->where('name', $categoryName)
                    ->value('id');
            }
$product = Product::create([
                'user_id' => ($user && !$user->isSuperAdmin()) ? $user->id : null,
                'sku' => $sku,
                'barcode' => $data['barcode'] ?? null,
                'name' => $name,
                'description' => $this->sanitizeDescription($data['description'] ?? null),
                'brand' => $data['brand'] ?? null,
                'category' => $categoryName !== '' ? $categoryName : null,
                'category_id' => $categoryId,
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
$skipped = $skippedEmpty + $skippedInvalid + $skippedMissing + $skippedLimit + $skippedDuplicate;
        $details = [
            "boş satır: {$skippedEmpty}",
            "geçersiz satır: {$skippedInvalid}",
            "eksik zorunlu alan: {$skippedMissing}",
            "abonelik limiti: {$skippedLimit}",
            "aynı SKU: {$skippedDuplicate}",
        ];

        return back()->with('success', "İçe aktarma tamamlandı. Başarılı: {$imported}, Atlanan: {$skipped} (" . implode(', ', $details) . ")");
    }

    public function create()
    {
        $user = auth()->user();
        $categoryMappingEnabled = (bool) AppSetting::getValue('category_mapping_enabled', true);
        $categoryMappingInlineEnabled = (bool) AppSetting::getValue('category_mapping_inline_enabled', true);
        $categories = Category::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();
        $brands = Brand::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        return view('admin.products.create', compact('categories', 'brands', 'categoryMappingEnabled', 'categoryMappingInlineEnabled'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $skuRule = ['nullable', 'string'];
        if ($user && !$user->isSuperAdmin()) {
            $skuRule[] = Rule::unique('products', 'sku')->where('user_id', $user->id);
        }
$validated = $request->validate(['sku' => $skuRule,
            'barcode' => 'nullable|string',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'brand' => 'nullable|string',
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', $user?->id),
            ],
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
        if (empty($validated['sku'])) {
            $validated['sku'] = $this->generateSku($user);
        }
        if (!empty($validated['category_id'])) {
            $categoryName = \App\Models\Category::query()
                ->where('user_id', $user?->id)
                ->whereKey($validated['category_id'])
                ->value('name');
            $validated['category'] = $categoryName;
        } else {
            $validated['category'] = null;
        }

        if ($user && !$user->isSuperAdmin()) {
            $subscription = $user->subscription;
            if (!$subscription || !$subscription->isActive() || !$subscription->canAddProduct()) {
                if ($subscription && $subscription->isActive() && !$subscription->canAddProduct()) {
                    $plan = $subscription->plan;
                    event(new QuotaExceeded(
                        $user->id,
                        'products',
                        (int) ($plan?->max_products ?? 0),
                        (int) $subscription->current_products_count,
                        'monthly',
                        null,
                        now()->toDateTimeString()
                    ));
                }
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
$subscription = $user->subscription?->fresh();
$plan = $subscription?->plan;
$limit = (int) ($plan?->max_products ?? 0);
            if ($limit > 0 && $subscription && $subscription->isActive()) {
                $used = (int) ($subscription->current_products_count ?? 0);
                if ($used >= (int) ceil($limit * 0.8)) {
                    event(new QuotaWarningReached(
                        $user->id,
                        'products',
                        $used,
                        $limit,
                        80,
                        null,
                        now()->toDateTimeString()
                    ));
                }
            }
        }

        return redirect()->route('portal.products.index')
            ->with('success', 'Ürün başarıyla oluşturuldu.');
    }

    public function show(Product $product)
    {
        $this->ensureOwner($product);
        $product->load('marketplaceProducts.marketplace', 'categoryRelation');

        // Show marketplace names even before API credentials are connected.
        $availableMarketplaces = \App\Models\Marketplace::query()
            ->orderBy('name')
            ->get();

        return view('admin.products.show', compact('product', 'availableMarketplaces'));
    }

    public function edit(Product $product)
    {
        $this->ensureOwner($product);
        $user = auth()->user();
        $categoryMappingEnabled = (bool) AppSetting::getValue('category_mapping_enabled', true);
        $categoryMappingInlineEnabled = (bool) AppSetting::getValue('category_mapping_inline_enabled', true);
        $categories = Category::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();
        $brands = Brand::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        return view('admin.products.edit', compact('product', 'categories', 'brands', 'categoryMappingEnabled', 'categoryMappingInlineEnabled'));
    }

    public function update(Request $request, Product $product)
    {
        $this->ensureOwner($product);
        $user = $request->user();
        $skuRule = ['required', 'string'];
        if ($user && !$user->isSuperAdmin()) {
            $skuRule[] = Rule::unique('products', 'sku')
                ->where('user_id', $user->id)
                ->ignore($product->id);
        } elseif ($product->user_id) {
            $skuRule[] = Rule::unique('products', 'sku')
                ->where('user_id', $product->user_id)
                ->ignore($product->id);
        }
$validated = $request->validate(['sku' => $skuRule,
            'barcode' => 'nullable|string',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'brand' => 'nullable|string',
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', $product->user_id ?? $user?->id),
            ],
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
        if (!empty($validated['category_id'])) {
            $categoryName = \App\Models\Category::query()
                ->where('user_id', $product->user_id ?? $user?->id)
                ->whereKey($validated['category_id'])
                ->value('name');
            $validated['category'] = $categoryName;
        } else {
            $validated['category'] = null;
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
            $existingImages = is_array($product->images) ? $product->images : [];
            $mergedImages = array_values(array_unique(array_merge($existingImages, $uploadedImages)));
            $validated['images'] = $mergedImages;
            if (!$product->image_url) {
                $validated['image_url'] = $mergedImages[0];
            }
        }
$product->update($validated);

        return redirect()->route('portal.products.index')
            ->with('success', 'Ürün başarıyla güncellendi.');
    }

    public function quickUpdate(Request $request, Product $product)
    {
        $this->ensureOwner($product);

        $validated = $request->validate(['cost_price' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        $product->update(['cost_price' => ($validated['cost_price'] ?? '') !== '' ? $validated['cost_price'] : null,
            'price' => $validated['price'],
            'stock_quantity' => $validated['stock_quantity'],
        ]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'id' => $product->id,
                'cost_price' => $product->cost_price,
                'price' => $product->price,
                'stock_quantity' => $product->stock_quantity,
            ]);
        }

        return redirect()->route('portal.products.index')
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

        return redirect()->route('portal.products.index')
            ->with('success', 'Ürün başarıyla silindi.');
    }

    private function sanitizeDescription(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        return Purifier::clean($html, 'product_description');
    }

    private function generateSku($user = null): string
    {
        do {
            $sku = 'AUTO-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
            $existsQuery = Product::where('sku', $sku);
            if ($user && !$user->isSuperAdmin()) {
                $existsQuery->where('user_id', $user->id);
            }
        } while ($existsQuery->exists());

        return $sku;
    }
}


