<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CommissionTariffExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CommissionTariffs\CommissionTariffAssignRequest;
use App\Http\Requests\CommissionTariffs\CommissionTariffColumnMapRequest;
use App\Http\Requests\CommissionTariffs\CommissionTariffExportRequest;
use App\Http\Requests\CommissionTariffs\CommissionTariffListRequest;
use App\Http\Requests\CommissionTariffs\CommissionTariffRecalcRequest;
use App\Http\Requests\CommissionTariffs\CommissionTariffUploadRequest;
use App\Models\CommissionTariffAssignment;
use App\Models\CommissionTariffRow;
use App\Models\CommissionTariffUpload;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CommissionTariffs\CommissionTariffAssignmentService;
use App\Services\CommissionTariffs\CommissionTariffImportService;
use App\Services\CommissionTariffs\ProfitCalculator;
use App\Services\CommissionTariffs\ShippingFeeResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TrendyolOfferApiController extends Controller
{
    public function upload(CommissionTariffUploadRequest $request, CommissionTariffImportService $service): JsonResponse
    {
        $data = $service->storeUpload($request->user(), $request->file('file'), $request->input('marketplace'));

        return response()->json([
            'uploadId' => $data['upload']->id,
            'headers' => $data['headers'],
            'preview' => $data['rows'],
        ]);
    }

    public function columnMap(CommissionTariffColumnMapRequest $request, CommissionTariffImportService $service): JsonResponse
    {
        $tenantUserId = $this->tenantUserId($request);
        $upload = CommissionTariffUpload::query()
            ->whereKey((int) $request->input('uploadId'))
            ->where('uploaded_by', $tenantUserId)
            ->firstOrFail();

        $service->mapColumnsAndDispatch($upload, $request->input('mapping', []));

        return response()->json(['status' => 'queued']);
    }

    public function list(CommissionTariffListRequest $request, ProfitCalculator $calculator, ShippingFeeResolver $shippingResolver): JsonResponse
    {
        $tenantUserId = $this->tenantUserId($request);
        $query = ProductVariant::query()
            ->with(['product', 'commissionTariffAssignments'])
            ->whereHas('product', function ($sub) use ($tenantUserId) {
                $sub->where('user_id', $tenantUserId);
            });

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', '%'.$search.'%')
                    ->orWhere('barcode', 'like', '%'.$search.'%')
                    ->orWhereHas('product', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->whereHas('product', function ($sub) use ($categoryId) {
                $sub->where('category_id', $categoryId);
            });
        }

        $perPage = (int) ($request->input('per_page') ?? 50);
        $paginator = $query->paginate($perPage);

        $items = $paginator->getCollection()->map(function (ProductVariant $variant) use ($calculator, $shippingResolver) {
            $product = $variant->product;
            $assignment = $variant->commissionTariffAssignments->first();

            $currentPrice = $product?->price ?? 0;
            $shippingFee = $shippingResolver->resolve($product);
            $platformFee = (float) config('commission_tariffs.platform_service_fee', 0);

            $ranges = [];
            for ($i = 1; $i <= 4; $i++) {
                $min = $assignment?->{"range{$i}_min"};
                $max = $assignment?->{"range{$i}_max"};
                $percent = $assignment?->{"c{$i}_percent"};
                $salePrice = $max ?? $min ?? $currentPrice;

                $calc = $calculator->calculate([
                    'salePrice' => $salePrice,
                    'productCost' => $product?->cost_price ?? 0,
                    'productVatRate' => $product?->vat_rate ?? 0,
                    'commissionPercent' => $percent ?? 0,
                    'shippingFee' => $shippingFee,
                    'platformServiceFee' => $platformFee,
                    'commissionVatRate' => config('commission_tariffs.commission_vat_rate', 20),
                    'shippingVatRate' => config('commission_tariffs.shipping_vat_rate', 20),
                    'serviceVatRate' => config('commission_tariffs.service_vat_rate', 20),
                ]);

                $ranges[] = [
                    'min' => $min,
                    'max' => $max,
                    'commission_percent' => $percent,
                    'profit' => $calc['profit'],
                    'profit_rate' => $calc['profit_rate'],
                    'commission_vat' => $calc['commission_vat'],
                ];
            }

            return [
                'variant_id' => $variant->id,
                'product_id' => $product?->id,
                'name' => $product?->name,
                'category_id' => $product?->category_id,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'stock' => $variant->stock,
                'current_price' => $currentPrice,
                'ranges' => $ranges,
                'assignment' => $assignment,
            ];
        });

        $categories = \App\Models\Category::query()
            ->where('user_id', $tenantUserId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'data' => $items,
            'categories' => $categories,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function errors(Request $request, int $uploadId): JsonResponse
    {
        $tenantUserId = $this->tenantUserId($request);
        $upload = CommissionTariffUpload::query()
            ->whereKey($uploadId)
            ->where('uploaded_by', $tenantUserId)
            ->firstOrFail();

        $rows = CommissionTariffRow::query()
            ->where('upload_id', $upload->id)
            ->whereIn('status', ['invalid', 'unmatched'])
            ->orderBy('row_no')
            ->get(['row_no', 'error_message', 'raw']);

        return response()->json([
            'rows' => $rows,
        ]);
    }

    public function assign(CommissionTariffAssignRequest $request, CommissionTariffAssignmentService $service): JsonResponse
    {
        $tenantUserId = $this->tenantUserId($request);
        $product = Product::query()
            ->whereKey((int) $request->input('productId'))
            ->where('user_id', $tenantUserId)
            ->firstOrFail();

        $service->upsertAssignments(
            $request->input('marketplace'),
            $product,
            $request->input('variantIds', []),
            $request->input('ranges', []),
            (bool) $request->input('allVariants', false)
        );

        return response()->json(['status' => 'ok']);
    }

    public function recalc(CommissionTariffRecalcRequest $request, ProfitCalculator $calculator, ShippingFeeResolver $shippingResolver): JsonResponse
    {
        $tenantUserId = $this->tenantUserId($request);
        $product = Product::query()
            ->whereKey((int) $request->input('productId'))
            ->where('user_id', $tenantUserId)
            ->firstOrFail();
        $variantId = $request->input('variantId');

        $assignment = null;
        if ($variantId) {
            $variant = ProductVariant::query()
                ->whereKey((int) $variantId)
                ->where('product_id', $product->id)
                ->firstOrFail();

            $assignment = CommissionTariffAssignment::query()
                ->where('product_id', $product->id)
                ->where('variant_id', $variant->id)
                ->first();
        }

        $manualPrice = (float) $request->input('manualPrice');
        $shippingFee = $shippingResolver->resolve($product);
        $platformFee = (float) config('commission_tariffs.platform_service_fee', 0);

        $ranges = [];
        $chosenRange = null;
        for ($i = 1; $i <= 4; $i++) {
            $min = $assignment?->{"range{$i}_min"};
            $max = $assignment?->{"range{$i}_max"};
            $percent = $assignment?->{"c{$i}_percent"};
            $calc = $calculator->calculate([
                'salePrice' => $manualPrice,
                'productCost' => $product->cost_price ?? 0,
                'productVatRate' => $product->vat_rate ?? 0,
                'commissionPercent' => $percent ?? 0,
                'shippingFee' => $shippingFee,
                'platformServiceFee' => $platformFee,
                'commissionVatRate' => config('commission_tariffs.commission_vat_rate', 20),
                'shippingVatRate' => config('commission_tariffs.shipping_vat_rate', 20),
                'serviceVatRate' => config('commission_tariffs.service_vat_rate', 20),
            ]);

            $ranges[] = [
                'min' => $min,
                'max' => $max,
                'commission_percent' => $percent,
                'profit' => $calc['profit'],
                'profit_rate' => $calc['profit_rate'],
                'commission_vat' => $calc['commission_vat'],
            ];

            if ($min !== null && $max !== null && $manualPrice >= $min && $manualPrice <= $max) {
                $chosenRange = $i;
            }
        }

        return response()->json([
            'chosenRange' => $chosenRange,
            'ranges' => $ranges,
        ]);
    }

    public function export(CommissionTariffExportRequest $request)
    {
        $export = new CommissionTariffExport(
            $this->tenantUserId($request),
            $request->input('search'),
            $request->input('category_id'),
            $request->input('selected', [])
        );

        return Excel::download($export, 'trendyol-teklifler.xlsx');
    }

    private function tenantUserId(Request $request): int
    {
        return (int) $request->user()->id;
    }
}

