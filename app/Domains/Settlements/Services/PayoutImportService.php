<?php

namespace App\Domains\Settlements\Services;

use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\PayoutRow;
use App\Models\MarketplaceAccount;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class PayoutImportService
{
    public function __construct(
        private readonly TenantRuleResolver $tenantRuleResolver
    ) {
    }

    /**
     * @return array{payout:Payout,rows:int}
     */
    public function import(
        UploadedFile $file,
        int $tenantId,
        int $accountId,
        string $marketplace,
        ?string $periodStart = null,
        ?string $periodEnd = null
    ): array {
        $storedPath = $file->store('imports/settlements', 'local');
        $absolute = Storage::disk('local')->path($storedPath);
        $hash = hash_file('sha256', $absolute) ?: null;
        $account = MarketplaceAccount::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($accountId);

        $payout = Payout::query()->create([
            'tenant_id' => $tenantId,
            'marketplace' => $marketplace,
            'marketplace_integration_id' => $account->marketplace_integration_id,
            'marketplace_account_id' => $accountId,
            'account_id' => $accountId,
            'payout_reference' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'payout_no' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'period_start' => $periodStart ?: now()->toDateString(),
            'period_end' => $periodEnd ?: now()->toDateString(),
            'currency' => 'TRY',
            'status' => 'EXPECTED',
            'imported_at' => now(),
            'file_name' => $file->getClientOriginalName(),
            'file_hash' => $hash,
            'raw_payload' => ['path' => $storedPath],
        ]);

        $rows = $this->readRows($absolute, $file->getClientOriginalExtension());
        $mapped = $this->mapRows($rows, $tenantId, $marketplace);

        foreach ($mapped as $row) {
            PayoutRow::query()->create([
                'payout_id' => $payout->id,
                'order_no' => $row['order_no'],
                'package_id' => $row['package_id'],
                'type' => $row['type'],
                'gross_amount' => $row['gross_amount'],
                'vat_amount' => $row['vat_amount'],
                'net_amount' => $row['net_amount'],
                'currency' => $row['currency'],
                'occurred_at' => $row['occurred_at'],
                'raw' => $row['raw'],
            ]);
        }

        return [
            'payout' => $payout->fresh(['rows']),
            'rows' => count($mapped),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function readRows(string $absolutePath, string $ext): array
    {
        $ext = strtolower($ext);
        if ($ext === 'csv') {
            $raw = array_map('str_getcsv', file($absolutePath) ?: []);
            if (empty($raw)) {
                return [];
            }

            $header = array_map(fn ($v) => strtolower(trim((string) $v)), $raw[0]);
            $rows = [];
            foreach (array_slice($raw, 1) as $line) {
                if (!is_array($line) || count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }
                $rows[] = array_combine($header, array_pad($line, count($header), null)) ?: [];
            }

            return $rows;
        }

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            $sheets = Excel::toArray([], $absolutePath);
            $firstSheet = $sheets[0] ?? [];
            if (count($firstSheet) < 2) {
                return [];
            }

            $header = array_map(fn ($v) => strtolower(trim((string) $v)), $firstSheet[0]);
            $rows = [];
            foreach (array_slice($firstSheet, 1) as $line) {
                if (!is_array($line) || count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }
                $rows[] = array_combine($header, array_pad($line, count($header), null)) ?: [];
            }

            return $rows;
        }

        throw new RuntimeException('Unsupported file format. Use CSV/XLSX.');
    }

    /**
     * @param  array<int,array<string,mixed>>  $rows
     * @return array<int,array<string,mixed>>
     */
    private function mapRows(array $rows, int $tenantId, string $marketplace): array
    {
        $mappingRules = collect($this->tenantRuleResolver->mapRowTypeRules($tenantId, $marketplace));

        $defaultMap = collect([
            'SALE' => 'sale',
            'COMMISSION' => 'commission',
            'SHIPPING' => 'shipping',
            'SERVICE_FEE' => 'service_fee',
            'COUPON' => 'coupon',
            'REFUND' => 'refund',
            'PENALTY' => 'penalty',
            'OTHER' => 'other',
        ]);

        $allMap = $defaultMap->merge($mappingRules);

        return collect($rows)->map(function (array $row) use ($allMap): array {
            $rowTypeRaw = strtoupper((string) ($row['type'] ?? $row['row_type'] ?? $row['line_type'] ?? 'OTHER'));
            $type = (string) ($allMap[$rowTypeRaw] ?? 'other');

            $gross = round((float) ($row['gross_amount'] ?? $row['gross'] ?? 0), 2);
            $vat = round((float) ($row['vat_amount'] ?? $row['vat'] ?? 0), 2);
            $net = round((float) ($row['net_amount'] ?? $row['net'] ?? ($gross - $vat)), 2);

            return [
                'order_no' => (string) ($row['order_no'] ?? $row['order_number'] ?? $row['siparis_no'] ?? ''),
                'package_id' => (string) ($row['package_id'] ?? $row['shipment_id'] ?? $row['paket_id'] ?? ''),
                'type' => in_array($type, ['sale', 'commission', 'shipping', 'service_fee', 'coupon', 'refund', 'penalty', 'other'], true)
                    ? $type
                    : 'other',
                'gross_amount' => $gross,
                'vat_amount' => $vat,
                'net_amount' => $net,
                'currency' => (string) ($row['currency'] ?? 'TRY'),
                'occurred_at' => !empty($row['occurred_at']) ? $row['occurred_at'] : null,
                'raw' => $row,
            ];
        })->values()->all();
    }
}
