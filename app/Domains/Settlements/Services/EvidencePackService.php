<?php

namespace App\Domains\Settlements\Services;

use App\Domains\Settlements\Models\Dispute;
use App\Domains\Settlements\Models\Payout;
use App\Domains\Settlements\Models\Reconciliation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EvidencePackService
{
    public function generate(Dispute $dispute, ?int $actorId = null): Dispute
    {
        $dispute->evidence_pack_status = 'processing';
        $dispute->save();

        $payout = Payout::query()->withoutGlobalScope('tenant_scope')->find($dispute->payout_id);
        $reconciliations = Reconciliation::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $dispute->tenant_id)
            ->where('payout_id', $dispute->payout_id)
            ->with(['payout.rows', 'order.financialItems'])
            ->get();

        $summary = [
            'dispute_id' => (int) $dispute->id,
            'tenant_id' => (int) $dispute->tenant_id,
            'payout_id' => (int) $dispute->payout_id,
            'payout_no' => (string) ($payout?->payout_no ?? $payout?->payout_reference ?? ''),
            'marketplace' => (string) ($payout?->marketplace ?? ''),
            'period_start' => optional($payout?->period_start)->toDateString(),
            'period_end' => optional($payout?->period_end)->toDateString(),
            'dispute_type' => (string) $dispute->dispute_type,
            'total_diff' => round((float) $reconciliations->sum('diff_total_net'), 2),
            'finding_count' => (int) $reconciliations->sum(function (Reconciliation $r): int {
                $findings = is_array($r->loss_findings_json) ? $r->loss_findings_json : [];
                return count($findings);
            }),
        ];

        $appealTemplate = $this->buildAppealTemplate($summary);

        $snapshot = [
            'summary' => $summary,
            'appeal_template_tr' => $appealTemplate,
            'reconciliations' => $reconciliations->map(function (Reconciliation $row): array {
                return [
                    'id' => $row->id,
                    'match_key' => $row->match_key,
                    'status' => $row->status,
                    'diff_total_net' => (float) $row->diff_total_net,
                    'diff_breakdown_json' => $row->diff_breakdown_json,
                    'findings' => $row->loss_findings_json,
                ];
            })->values()->all(),
            'payout_rows' => $reconciliations->flatMap(function (Reconciliation $r): array {
                return $r->payout?->rows?->map(fn ($row) => $row->toArray())->all() ?? [];
            })->values()->all(),
            'expected_items' => $reconciliations->flatMap(function (Reconciliation $r): array {
                return $r->order?->financialItems?->map(fn ($item) => $item->toArray())->all() ?? [];
            })->values()->all(),
        ];

        $basePath = sprintf(
            'exports/evidence-packs/tenant-%d/dispute-%d-%s',
            (int) $dispute->tenant_id,
            (int) $dispute->id,
            Str::uuid()
        );

        $jsonPath = $basePath.'.json';
        Storage::disk('local')->put($jsonPath, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $xlsxPath = $basePath.'.xlsx';
        $xlsxGenerated = $this->tryGenerateXlsx($xlsxPath, $snapshot);

        $dispute->evidence_pack_status = 'ready';
        $dispute->evidence_pack_path = $xlsxGenerated ? $xlsxPath : $jsonPath;
        $dispute->evidence_pack_generated_at = now();
        $dispute->evidence_pack_meta_json = [
            'generated_by' => $actorId,
            'version' => 'v1.1',
            'format' => $xlsxGenerated ? 'xlsx' : 'json',
            'snapshot_path' => $jsonPath,
            'counts' => [
                'reconciliations' => count($snapshot['reconciliations']),
                'findings' => (int) $summary['finding_count'],
                'payout_rows' => count($snapshot['payout_rows']),
                'expected_items' => count($snapshot['expected_items']),
            ],
            'totals' => [
                'diff_total' => (float) $summary['total_diff'],
            ],
            'appeal_template_tr' => $appealTemplate,
        ];
        $dispute->notes = trim((string) ($dispute->notes ?? '') . "\n\n" . $appealTemplate);
        $dispute->updated_by = $actorId;
        $dispute->save();

        return $dispute->fresh();
    }

    /**
     * @param  array<string,mixed>  $snapshot
     */
    private function tryGenerateXlsx(string $xlsxPath, array $snapshot): bool
    {
        if (!class_exists(Spreadsheet::class)) {
            return false;
        }

        $spreadsheet = new Spreadsheet();

        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Summary');
        $this->writeAssoc($summarySheet, $snapshot['summary'] ?? []);

        $findingsSheet = $spreadsheet->createSheet();
        $findingsSheet->setTitle('Findings');
        $this->writeRows($findingsSheet, $this->flattenFindings($snapshot['reconciliations'] ?? []));

        $payoutRowsSheet = $spreadsheet->createSheet();
        $payoutRowsSheet->setTitle('PayoutRows');
        $this->writeRows($payoutRowsSheet, $snapshot['payout_rows'] ?? []);

        $expectedSheet = $spreadsheet->createSheet();
        $expectedSheet->setTitle('ExpectedItems');
        $this->writeRows($expectedSheet, $snapshot['expected_items'] ?? []);

        $writer = new Xlsx($spreadsheet);
        $tmp = tempnam(sys_get_temp_dir(), 'evp_');
        if ($tmp === false) {
            return false;
        }

        $writer->save($tmp);
        $bytes = @file_get_contents($tmp);
        @unlink($tmp);
        if ($bytes === false) {
            return false;
        }

        Storage::disk('local')->put($xlsxPath, $bytes);
        return true;
    }

    /**
     * @param  array<string,mixed>  $assoc
     */
    private function writeAssoc(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $assoc): void
    {
        $row = 1;
        foreach ($assoc as $key => $value) {
            $sheet->setCellValue("A{$row}", (string) $key);
            $sheet->setCellValue("B{$row}", is_scalar($value) || $value === null ? (string) $value : json_encode($value));
            $row++;
        }
    }

    /**
     * @param  array<int,array<string,mixed>>  $rows
     */
    private function writeRows(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $headers = array_keys((array) $rows[0]);
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 1, (string) $header);
            $col++;
        }

        $r = 2;
        foreach ($rows as $row) {
            $c = 1;
            foreach ($headers as $header) {
                $value = $row[$header] ?? null;
                $sheet->setCellValueByColumnAndRow(
                    $c,
                    $r,
                    is_scalar($value) || $value === null ? (string) $value : json_encode($value)
                );
                $c++;
            }
            $r++;
        }
    }

    /**
     * @param  array<int,array<string,mixed>>  $reconciliations
     * @return array<int,array<string,mixed>>
     */
    private function flattenFindings(array $reconciliations): array
    {
        $rows = [];
        foreach ($reconciliations as $rec) {
            $findings = is_array($rec['findings'] ?? null) ? $rec['findings'] : [];
            foreach ($findings as $finding) {
                $rows[] = [
                    'reconciliation_id' => $rec['id'] ?? null,
                    'match_key' => $rec['match_key'] ?? null,
                    'code' => $finding['code'] ?? null,
                    'type' => $finding['type'] ?? null,
                    'severity' => $finding['severity'] ?? null,
                    'amount' => $finding['amount'] ?? null,
                    'confidence' => $finding['confidence_score'] ?? null,
                    'detail' => $finding['detail'] ?? null,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array<string,mixed>  $summary
     */
    private function buildAppealTemplate(array $summary): string
    {
        $marketplace = (string) ($summary['marketplace'] ?? 'pazaryeri');
        $periodStart = (string) ($summary['period_start'] ?? '-');
        $periodEnd = (string) ($summary['period_end'] ?? '-');
        $totalDiff = number_format((float) ($summary['total_diff'] ?? 0), 2, ',', '.');
        $findingCount = (int) ($summary['finding_count'] ?? 0);

        return trim(implode("\n", [
            "Sayin {$marketplace} destek ekibi,",
            " {$periodStart} - {$periodEnd} donemi hak edis mutabakat incelememizde uyumsuzluk tespit edilmistir.",
            " Toplam fark: {$totalDiff} TRY",
            " Bulgu adedi: {$findingCount}",
            " Ilgili siparis/kalem ve kanit paketleri ekte sunulmustur.",
            " Gereginin yapilarak eksik/hatali kesintilerin duzeltilmesini rica ederiz.",
        ]));
    }
}
