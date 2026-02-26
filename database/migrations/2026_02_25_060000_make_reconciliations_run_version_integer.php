<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reconciliations', 'run_version')) {
            return;
        }

        // Drop indexes that include run_version before column replacement.
        $this->dropRunVersionIndexes();

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement("ALTER TABLE reconciliations ADD COLUMN run_version_int INTEGER NOT NULL DEFAULT 1");
            $this->backfillRunVersionInt();
            DB::statement("ALTER TABLE reconciliations DROP COLUMN run_version");
            DB::statement("ALTER TABLE reconciliations RENAME COLUMN run_version_int TO run_version");
        } else {
            Schema::table('reconciliations', function (Blueprint $table) {
                $table->unsignedInteger('run_version_int')->default(1)->after('run_hash');
            });

            $this->backfillRunVersionInt();

            Schema::table('reconciliations', function (Blueprint $table) {
                $table->dropColumn('run_version');
            });

            DB::statement("ALTER TABLE reconciliations CHANGE run_version_int run_version INT UNSIGNED NOT NULL DEFAULT 1");
        }

        Schema::table('reconciliations', function (Blueprint $table) {
            $table->index(['run_version']);
            $table->index(['payout_id', 'run_version'], 'reconciliations_payout_run_version_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('reconciliations', 'run_version')) {
            return;
        }

        $this->dropRunVersionIndexes();

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement("ALTER TABLE reconciliations ADD COLUMN run_version_text VARCHAR(20) DEFAULT '1'");
            DB::table('reconciliations')
                ->orderBy('id')
                ->select(['id', 'run_version'])
                ->chunkById(1000, function ($rows): void {
                    foreach ($rows as $row) {
                        DB::table('reconciliations')
                            ->where('id', $row->id)
                            ->update(['run_version_text' => (string) ((int) ($row->run_version ?? 1))]);
                    }
                });
            DB::statement("ALTER TABLE reconciliations DROP COLUMN run_version");
            DB::statement("ALTER TABLE reconciliations RENAME COLUMN run_version_text TO run_version");
        } else {
            Schema::table('reconciliations', function (Blueprint $table) {
                $table->string('run_version_text', 20)->default('1')->after('run_hash');
            });

            DB::table('reconciliations')
                ->orderBy('id')
                ->select(['id', 'run_version'])
                ->chunkById(1000, function ($rows): void {
                    foreach ($rows as $row) {
                        DB::table('reconciliations')
                            ->where('id', $row->id)
                            ->update(['run_version_text' => (string) ((int) ($row->run_version ?? 1))]);
                    }
                });

            Schema::table('reconciliations', function (Blueprint $table) {
                $table->dropColumn('run_version');
            });

            DB::statement("ALTER TABLE reconciliations CHANGE run_version_text run_version VARCHAR(20) NOT NULL DEFAULT '1'");
        }

        Schema::table('reconciliations', function (Blueprint $table) {
            $table->index(['run_version']);
            $table->index(['payout_id', 'run_version'], 'reconciliations_payout_run_version_idx');
        });
    }

    private function dropRunVersionIndexes(): void
    {
        try {
            Schema::table('reconciliations', function (Blueprint $table) {
                $table->dropIndex('reconciliations_payout_run_version_idx');
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('reconciliations', function (Blueprint $table) {
                $table->dropIndex(['run_version']);
            });
        } catch (\Throwable) {
        }
    }

    private function backfillRunVersionInt(): void
    {
        DB::table('reconciliations')
            ->orderBy('id')
            ->select(['id', 'run_version'])
            ->chunkById(1000, function ($rows): void {
                foreach ($rows as $row) {
                    $raw = strtolower(trim((string) ($row->run_version ?? '1')));
                    $value = match ($raw) {
                        'v1.1', '2' => 2,
                        'v1.0', '1' => 1,
                        default => (int) preg_replace('/[^0-9]/', '', $raw) ?: 1,
                    };

                    DB::table('reconciliations')
                        ->where('id', $row->id)
                        ->update(['run_version_int' => max(1, $value)]);
                }
            });
    }
};
