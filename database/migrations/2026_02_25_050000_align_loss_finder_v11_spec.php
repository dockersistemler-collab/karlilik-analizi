<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reconciliations', function (Blueprint $table) {
            if (Schema::hasColumn('reconciliations', 'run_version')) {
                // Keep existing column for compatibility; set numeric default semantics.
                DB::table('reconciliations')
                    ->whereNull('run_version')
                    ->orWhere('run_version', '')
                    ->update(['run_version' => '1']);
            }

            $table->index(['payout_id', 'run_version'], 'reconciliations_payout_run_version_idx');
        });

        Schema::table('loss_findings', function (Blueprint $table) {
            if (!Schema::hasColumn('loss_findings', 'confidence')) {
                $table->unsignedTinyInteger('confidence')->default(50)->after('amount');
            }
            if (!Schema::hasColumn('loss_findings', 'meta')) {
                $table->json('meta')->nullable()->after('detail');
            }

            $table->index(['payout_id', 'severity'], 'loss_findings_payout_severity_idx');
            $table->index(['payout_id', 'suggested_dispute_type'], 'loss_findings_payout_dispute_type_idx');
        });

        Schema::table('loss_patterns', function (Blueprint $table) {
            if (!Schema::hasColumn('loss_patterns', 'marketplace')) {
                $table->string('marketplace', 50)->nullable()->after('tenant_id');
            }
            if (!Schema::hasColumn('loss_patterns', 'code')) {
                $table->string('code', 120)->nullable()->after('pattern_key');
            }
            if (!Schema::hasColumn('loss_patterns', 'type')) {
                $table->string('type', 60)->nullable()->after('code');
            }
            if (!Schema::hasColumn('loss_patterns', 'occurrences')) {
                $table->unsignedInteger('occurrences')->default(0)->after('type');
            }
            if (!Schema::hasColumn('loss_patterns', 'sample_finding_id')) {
                $table->unsignedBigInteger('sample_finding_id')->nullable()->after('last_seen_at');
            }
            if (!Schema::hasColumn('loss_patterns', 'meta')) {
                $table->json('meta')->nullable()->after('sample_finding_id');
            }
        });

        try {
            Schema::table('loss_patterns', function (Blueprint $table) {
                $table->unique(['tenant_id', 'pattern_key'], 'loss_patterns_tenant_pattern_unique');
            });
        } catch (\Throwable) {
            // no-op for existing/duplicate environments
        }

        Schema::table('loss_patterns', function (Blueprint $table) {
            $table->index(['tenant_id', 'last_seen_at'], 'loss_patterns_tenant_last_seen_idx');
            $table->index(['tenant_id', 'total_amount'], 'loss_patterns_tenant_total_amount_idx');
        });

        Schema::table('disputes', function (Blueprint $table) {
            $table->index(['payout_id', 'evidence_pack_generated_at'], 'disputes_payout_evidence_generated_idx');
        });

        Schema::table('reconciliation_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('reconciliation_rules', 'scope')) {
                $table->enum('scope', ['global', 'tenant'])->default('global')->after('tenant_id');
            }
            if (!Schema::hasColumn('reconciliation_rules', 'valid_from')) {
                $table->dateTime('valid_from')->nullable()->after('scope');
            }
            if (!Schema::hasColumn('reconciliation_rules', 'valid_to')) {
                $table->dateTime('valid_to')->nullable()->after('valid_from');
            }

            $table->index(
                ['marketplace', 'rule_type', 'key', 'tenant_id', 'is_active'],
                'reconciliation_rules_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('reconciliation_rules', function (Blueprint $table) {
            if (Schema::hasColumn('reconciliation_rules', 'valid_to')) {
                $table->dropColumn('valid_to');
            }
            if (Schema::hasColumn('reconciliation_rules', 'valid_from')) {
                $table->dropColumn('valid_from');
            }
            if (Schema::hasColumn('reconciliation_rules', 'scope')) {
                $table->dropColumn('scope');
            }
        });

        Schema::table('loss_patterns', function (Blueprint $table) {
            if (Schema::hasColumn('loss_patterns', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('loss_patterns', 'sample_finding_id')) {
                $table->dropColumn('sample_finding_id');
            }
            if (Schema::hasColumn('loss_patterns', 'occurrences')) {
                $table->dropColumn('occurrences');
            }
            if (Schema::hasColumn('loss_patterns', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('loss_patterns', 'code')) {
                $table->dropColumn('code');
            }
            if (Schema::hasColumn('loss_patterns', 'marketplace')) {
                $table->dropColumn('marketplace');
            }
        });

        Schema::table('loss_findings', function (Blueprint $table) {
            if (Schema::hasColumn('loss_findings', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('loss_findings', 'confidence')) {
                $table->dropColumn('confidence');
            }
        });
    }
};
