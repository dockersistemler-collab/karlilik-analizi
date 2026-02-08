<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('marketplace_carrier_mappings', 'external_carrier_code_normalized')) {
            Schema::table('marketplace_carrier_mappings', function (Blueprint $table) {
                $table->string('external_carrier_code_normalized')->nullable()->after('external_carrier_code');
            });
        }

        DB::table('marketplace_carrier_mappings')
            ->select(['id', 'external_carrier_code'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $normalized = $this->normalizeCarrier($row->external_carrier_code ?? null);
                    DB::table('marketplace_carrier_mappings')
                        ->where('id', $row->id)
                        ->update(['external_carrier_code_normalized' => $normalized]);
                }
            });

        $hasMeta = Schema::hasColumn('marketplace_carrier_mappings', 'meta');
        $duplicates = DB::table('marketplace_carrier_mappings')
            ->select(['marketplace_code', 'external_carrier_code_normalized', DB::raw('COUNT(*) as cnt')])
            ->whereNotNull('external_carrier_code_normalized')
            ->groupBy('marketplace_code', 'external_carrier_code_normalized')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            $rows = DB::table('marketplace_carrier_mappings')
                ->where('marketplace_code', $duplicate->marketplace_code)
                ->where('external_carrier_code_normalized', $duplicate->external_carrier_code_normalized)
                ->orderByDesc('is_active')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();

            $keep = $rows->first();
            if (!$keep) {
                continue;
            }

            $dropIds = $rows->pluck('id')->filter(fn ($id) => $id !== $keep->id)->values();
            if ($dropIds->isEmpty()) {
                continue;
            }

            if ($hasMeta) {
                DB::table('marketplace_carrier_mappings')
                    ->whereIn('id', $dropIds)
                    ->update([
                        'is_active' => false,
                        'meta' => DB::raw("JSON_SET(COALESCE(meta, JSON_OBJECT()), '$.duplicate_of', {$keep->id}, '$.duplicate_reason', 'normalized_unique_conflict')"),
                    ]);
            } else {
                DB::table('marketplace_carrier_mappings')
                    ->whereIn('id', $dropIds)
                    ->update([
                        'is_active' => false,
                    ]);
            }
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('marketplace_carrier_mappings', function (Blueprint $table) {
                $table->index('external_carrier_code_normalized', 'mkt_carrier_norm_idx');
                $table->unique(['marketplace_code', 'external_carrier_code_normalized'], 'mkt_carrier_norm_uniq');
            });

            return;
        }

        $indexes = collect(DB::select('SHOW INDEX FROM `marketplace_carrier_mappings`'))
            ->pluck('Key_name')
            ->unique()
            ->values();

        Schema::table('marketplace_carrier_mappings', function (Blueprint $table) use ($indexes) {
            if (!$indexes->contains('mkt_carrier_norm_idx')) {
                $table->index('external_carrier_code_normalized', 'mkt_carrier_norm_idx');
            }
            if (!$indexes->contains('mkt_carrier_norm_uniq')) {
                $table->unique(['marketplace_code', 'external_carrier_code_normalized'], 'mkt_carrier_norm_uniq');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_carrier_mappings', function (Blueprint $table) {
            $table->dropUnique('mkt_carrier_norm_uniq');
            $table->dropIndex('mkt_carrier_norm_idx');
            $table->dropColumn('external_carrier_code_normalized');
        });
    }

    private function normalizeCarrier(?string $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        if (function_exists('mb_strtolower')) {
            $encoding = in_array('tr_TR', mb_list_encodings(), true) ? 'tr_TR' : 'UTF-8';
            $normalized = mb_strtolower($normalized, $encoding);
        } else {
            $normalized = strtolower($normalized);
        }

        $normalized = preg_replace('/[\\-_.]+/u', ' ', $normalized);
        $normalized = preg_replace('/\\s+/u', ' ', $normalized);
        $normalized = trim($normalized);

        return $normalized === '' ? null : $normalized;
    }
};
