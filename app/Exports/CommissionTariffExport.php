<?php

namespace App\Exports;

use App\Models\ProductVariant;
use App\Services\CommissionTariffs\ProfitCalculator;
use App\Services\CommissionTariffs\ShippingFeeResolver;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CommissionTariffExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private int $tenantUserId,
        private ?string $search,
        private ?int $categoryId,
        private array $selectedIds,
        private int $rangeCount = 4
    ) {
    }

    public function collection(): Collection
    {
        $query = ProductVariant::query()
            ->with(['product', 'commissionTariffAssignments'])
            ->whereHas('product', function ($sub) {
                $sub->where('user_id', $this->tenantUserId);
            });

        if ($this->selectedIds) {
            $query->whereIn('id', $this->selectedIds);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('sku', 'like', '%'.$this->search.'%')
                    ->orWhere('barcode', 'like', '%'.$this->search.'%')
                    ->orWhereHas('product', function ($sub) {
                        $sub->where('name', 'like', '%'.$this->search.'%');
                    });
            });
        }

        if ($this->categoryId) {
            $query->whereHas('product', function ($sub) {
                $sub->where('category_id', $this->categoryId);
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        $headings = [
            'Urun',
            'SKU',
            'Barkod',
            'Maliyet',
            'Kargo',
            'Hizmet Bedeli',
        ];

        for ($i = 1; $i <= $this->rangeCount; $i++) {
            $headings[] = "Aralik {$i}";
            $headings[] = "Satis Fiyat {$i}";
            $headings[] = "Komisyon %{$i}";
            $headings[] = "Komisyon Tutar {$i}";
            $headings[] = "Komisyon KDV {$i}";
            $headings[] = "Net KDV {$i}";
            $headings[] = "Kar {$i}";
            $headings[] = "Kar %{$i}";
        }

        return $headings;
    }

    public function map($row): array
    {
        $product = $row->product;
        $assignment = $row->commissionTariffAssignments->first();

        $calculator = app(ProfitCalculator::class);
        $shippingResolver = app(ShippingFeeResolver::class);
        $shippingFee = $shippingResolver->resolve($product);
        $platformFee = (float) config('commission_tariffs.platform_service_fee', 0);

        $out = [
            $product?->name,
            $row->sku,
            $row->barcode,
            (float) ($product?->cost_price ?? 0),
            $shippingFee,
            $platformFee,
        ];

        for ($i = 1; $i <= $this->rangeCount; $i++) {
            $min = $assignment?->{"range{$i}_min"};
            $max = $assignment?->{"range{$i}_max"};
            $percent = $assignment?->{"c{$i}_percent"};
            $salePrice = $max ?? $min ?? ($product?->price ?? 0);

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

            $out[] = $this->formatRange($min, $max);
            $out[] = $salePrice;
            $out[] = $percent;
            $out[] = $calc['commission_amount'];
            $out[] = $calc['commission_vat'];
            $out[] = $calc['net_vat'];
            $out[] = $calc['profit'];
            $out[] = $calc['profit_rate'];
        }

        return $out;
    }

    private function formatRange($min, $max): string
    {
        if ($min === null && $max === null) {
            return '';
        }
        if ($min === null) {
            return '0 - '.$max;
        }
        if ($max === null) {
            return $min.' +';
        }
        return $min.' - '.$max;
    }
}
