<?php

namespace App\Imports;

use App\Models\CommissionTariffRow;
use App\Models\CommissionTariffUpload;
use App\Services\CommissionTariffs\CommissionTariffMatcher;
use App\Support\TRNumberParser;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class CommissionTariffRowsImport implements OnEachRow, WithHeadingRow, WithChunkReading
{
    private CommissionTariffUpload $upload;

    public function __construct(CommissionTariffUpload $upload)
    {
        $this->upload = $upload;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function onRow(Row $row): void
    {
        $data = $row->toArray();
        $rowNo = $row->getIndex();
        $mapping = $this->upload->column_map ?? [];

        $get = function (string $key) use ($mapping, $data) {
            $mapped = $mapping[$key] ?? null;
            if (!$mapped) {
                return null;
            }
            return $data[$mapped] ?? null;
        };

        $sku = $get('merchantSku') ?? $get('sku');
        $barcode = $get('barcode');
        $productId = $get('productId');

        $ranges = [];
        $invalid = false;
        $errors = [];
        for ($i = 1; $i <= 4; $i++) {
            $minRaw = $get("range{$i}Min");
            $maxRaw = $get("range{$i}Max");
            $percentRaw = $get("commission{$i}Percent");

            $min = TRNumberParser::parse($minRaw);
            $max = TRNumberParser::parse($maxRaw);
            $percent = TRNumberParser::parse($percentRaw);

            if ($minRaw !== null && $min === null) {
                $invalid = true;
                $errors[] = "range{$i}_min";
            }
            if ($maxRaw !== null && $max === null) {
                $invalid = true;
                $errors[] = "range{$i}_max";
            }
            if ($percentRaw !== null && $percent === null) {
                $invalid = true;
                $errors[] = "commission{$i}_percent";
            }

            $ranges[$i] = [
                'min' => $min,
                'max' => $max,
                'percent' => $percent,
            ];
        }

        $matcher = app(CommissionTariffMatcher::class);
        $match = $matcher->match(
            is_string($sku) ? trim($sku) : null,
            is_string($barcode) ? trim($barcode) : null,
            $productId,
            (int) $this->upload->uploaded_by
        );

        $status = $match['status'];
        $errorMessage = $match['error'];
        $productIdMatched = $match['product_id'];
        $variantIdMatched = $match['variant_id'];
        if ($invalid) {
            $status = 'invalid';
            $errorMessage = 'Gecersiz alanlar: '.implode(', ', $errors);
        }

        CommissionTariffRow::create([
            'upload_id' => $this->upload->id,
            'row_no' => $rowNo,
            'raw' => $data,
            'product_match_key' => $productId ? (string) $productId : null,
            'variant_match_key' => $sku ?: $barcode,
            'product_id' => $productIdMatched,
            'variant_id' => $variantIdMatched,
            'range1_min' => $ranges[1]['min'],
            'range1_max' => $ranges[1]['max'],
            'c1_percent' => $ranges[1]['percent'],
            'range2_min' => $ranges[2]['min'],
            'range2_max' => $ranges[2]['max'],
            'c2_percent' => $ranges[2]['percent'],
            'range3_min' => $ranges[3]['min'],
            'range3_max' => $ranges[3]['max'],
            'c3_percent' => $ranges[3]['percent'],
            'range4_min' => $ranges[4]['min'],
            'range4_max' => $ranges[4]['max'],
            'c4_percent' => $ranges[4]['percent'],
            'status' => $status,
            'error_message' => $errorMessage,
        ]);

        $this->upload->increment('processed_rows');
        if ($status === 'matched') {
            $this->upload->increment('matched_rows');
        }
        if ($status !== 'matched') {
            $this->upload->increment('error_rows');
        }
    }
}
