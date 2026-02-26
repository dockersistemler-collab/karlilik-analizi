<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reconciliations', function (Blueprint $table) {
            if (!Schema::hasColumn('reconciliations', 'findings_summary_json')) {
                $table->json('findings_summary_json')->nullable()->after('loss_findings_json');
            }
            if (!Schema::hasColumn('reconciliations', 'run_hash')) {
                $table->string('run_hash', 64)->nullable()->after('findings_summary_json');
            }
            if (!Schema::hasColumn('reconciliations', 'run_version')) {
                $table->string('run_version', 20)->default('v1.1')->after('run_hash');
            }
        });

        Schema::table('reconciliations', function (Blueprint $table) {
            $table->index(['payout_id', 'run_hash']);
            $table->index(['run_version']);
        });

        if (!Schema::hasTable('loss_findings')) {
            Schema::create('loss_findings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('reconciliation_id')->constrained('reconciliations')->cascadeOnDelete();
                $table->foreignId('payout_id')->constrained('payouts')->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->string('code', 120)->index();
                $table->string('title')->nullable();
                $table->text('detail')->nullable();
                $table->string('severity', 20)->nullable()->index();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('type', 60)->nullable()->index();
                $table->string('suggested_dispute_type', 60)->nullable();
                $table->decimal('confidence_score', 5, 2)->default(0);
                $table->string('pattern_key', 120)->nullable()->index();
                $table->json('meta_json')->nullable();
                $table->dateTime('occurred_at')->nullable()->index();
                $table->timestamps();

                $table->index(['tenant_id', 'payout_id', 'code']);
            });
        }

        if (!Schema::hasTable('loss_patterns')) {
            Schema::create('loss_patterns', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('payout_id')->nullable()->constrained('payouts')->nullOnDelete();
                $table->string('run_hash', 64)->nullable()->index();
                $table->string('run_version', 20)->default('v1.1')->index();
                $table->string('pattern_key', 120)->index();
                $table->string('finding_code', 120)->index();
                $table->string('severity', 20)->nullable();
                $table->unsignedInteger('occurrence_count')->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->decimal('avg_confidence', 5, 2)->default(0);
                $table->dateTime('first_seen_at')->nullable();
                $table->dateTime('last_seen_at')->nullable();
                $table->json('examples_json')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'payout_id', 'run_hash', 'pattern_key', 'finding_code'], 'loss_patterns_unique_run');
            });
        }

        Schema::table('disputes', function (Blueprint $table) {
            if (!Schema::hasColumn('disputes', 'evidence_pack_status')) {
                $table->string('evidence_pack_status', 30)->nullable()->after('evidence_json');
            }
            if (!Schema::hasColumn('disputes', 'evidence_pack_path')) {
                $table->string('evidence_pack_path')->nullable()->after('evidence_pack_status');
            }
            if (!Schema::hasColumn('disputes', 'evidence_pack_generated_at')) {
                $table->dateTime('evidence_pack_generated_at')->nullable()->after('evidence_pack_path');
            }
            if (!Schema::hasColumn('disputes', 'evidence_pack_meta_json')) {
                $table->json('evidence_pack_meta_json')->nullable()->after('evidence_pack_generated_at');
            }
        });

        Schema::table('disputes', function (Blueprint $table) {
            $table->index(['evidence_pack_status']);
        });

        Schema::table('reconciliation_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('reconciliation_rules', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('reconciliation_rules', 'scope_type')) {
                $table->string('scope_type', 20)->default('global')->after('tenant_id')->index();
            }
            if (!Schema::hasColumn('reconciliation_rules', 'scope_key')) {
                $table->string('scope_key', 120)->nullable()->after('scope_type')->index();
            }
        });

        Schema::table('payouts', function (Blueprint $table) {
            if (!Schema::hasColumn('payouts', 'regression_flag')) {
                $table->boolean('regression_flag')->default(false)->after('status')->index();
            }
            if (!Schema::hasColumn('payouts', 'regression_note')) {
                $table->text('regression_note')->nullable()->after('regression_flag');
            }
            if (!Schema::hasColumn('payouts', 'regression_checked_at')) {
                $table->dateTime('regression_checked_at')->nullable()->after('regression_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            if (Schema::hasColumn('payouts', 'regression_checked_at')) {
                $table->dropColumn('regression_checked_at');
            }
            if (Schema::hasColumn('payouts', 'regression_note')) {
                $table->dropColumn('regression_note');
            }
            if (Schema::hasColumn('payouts', 'regression_flag')) {
                $table->dropColumn('regression_flag');
            }
        });

        Schema::table('reconciliation_rules', function (Blueprint $table) {
            if (Schema::hasColumn('reconciliation_rules', 'scope_key')) {
                $table->dropColumn('scope_key');
            }
            if (Schema::hasColumn('reconciliation_rules', 'scope_type')) {
                $table->dropColumn('scope_type');
            }
            if (Schema::hasColumn('reconciliation_rules', 'tenant_id')) {
                $table->dropColumn('tenant_id');
            }
        });

        Schema::table('disputes', function (Blueprint $table) {
            if (Schema::hasColumn('disputes', 'evidence_pack_meta_json')) {
                $table->dropColumn('evidence_pack_meta_json');
            }
            if (Schema::hasColumn('disputes', 'evidence_pack_generated_at')) {
                $table->dropColumn('evidence_pack_generated_at');
            }
            if (Schema::hasColumn('disputes', 'evidence_pack_path')) {
                $table->dropColumn('evidence_pack_path');
            }
            if (Schema::hasColumn('disputes', 'evidence_pack_status')) {
                $table->dropColumn('evidence_pack_status');
            }
        });

        Schema::dropIfExists('loss_patterns');
        Schema::dropIfExists('loss_findings');

        Schema::table('reconciliations', function (Blueprint $table) {
            if (Schema::hasColumn('reconciliations', 'run_version')) {
                $table->dropColumn('run_version');
            }
            if (Schema::hasColumn('reconciliations', 'run_hash')) {
                $table->dropColumn('run_hash');
            }
            if (Schema::hasColumn('reconciliations', 'findings_summary_json')) {
                $table->dropColumn('findings_summary_json');
            }
        });
    }
};
