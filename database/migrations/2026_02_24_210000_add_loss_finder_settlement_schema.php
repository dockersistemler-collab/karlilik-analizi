<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            if (!Schema::hasColumn('payouts', 'marketplace')) {
                $table->string('marketplace', 50)->nullable()->after('tenant_id')->index();
            }
            if (!Schema::hasColumn('payouts', 'account_id')) {
                $table->unsignedBigInteger('account_id')->nullable()->after('marketplace_account_id')->index();
            }
            if (!Schema::hasColumn('payouts', 'payout_no')) {
                $table->string('payout_no', 120)->nullable()->after('payout_reference')->index();
            }
            if (!Schema::hasColumn('payouts', 'paid_at')) {
                $table->dateTime('paid_at')->nullable()->after('paid_date');
            }
            if (!Schema::hasColumn('payouts', 'imported_at')) {
                $table->dateTime('imported_at')->nullable()->after('paid_at')->index();
            }
            if (!Schema::hasColumn('payouts', 'file_name')) {
                $table->string('file_name')->nullable()->after('imported_at');
            }
            if (!Schema::hasColumn('payouts', 'file_hash')) {
                $table->string('file_hash', 64)->nullable()->after('file_name')->index();
            }
        });

        if (!Schema::hasTable('payout_rows')) {
            Schema::create('payout_rows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payout_id')->constrained('payouts')->cascadeOnDelete();
                $table->string('order_no')->nullable();
                $table->string('package_id')->nullable();
                $table->enum('type', ['sale', 'commission', 'shipping', 'service_fee', 'coupon', 'refund', 'penalty', 'other']);
                $table->decimal('gross_amount', 12, 2)->default(0);
                $table->decimal('vat_amount', 12, 2)->default(0);
                $table->decimal('net_amount', 12, 2)->default(0);
                $table->string('currency', 3)->default('TRY');
                $table->dateTime('occurred_at')->nullable();
                $table->json('raw')->nullable();
                $table->timestamps();

                $table->index(['payout_id', 'order_no']);
                $table->index(['order_no']);
                $table->index(['package_id']);
            });
        }

        if (!Schema::hasTable('order_financial_items')) {
            Schema::create('order_financial_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->string('marketplace', 50)->index();
                $table->enum('type', ['sale', 'commission', 'shipping', 'service_fee', 'coupon', 'refund', 'penalty', 'other']);
                $table->decimal('gross_amount', 12, 2)->default(0);
                $table->decimal('vat_amount', 12, 2)->default(0);
                $table->decimal('net_amount', 12, 2)->default(0);
                $table->string('currency', 3)->default('TRY');
                $table->enum('source', ['api', 'rule', 'manual'])->default('api');
                $table->json('raw_ref')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'type']);
            });
        }

        Schema::table('reconciliations', function (Blueprint $table) {
            if (!Schema::hasColumn('reconciliations', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('payout_id')->constrained('orders')->nullOnDelete();
            }
            if (!Schema::hasColumn('reconciliations', 'match_key')) {
                $table->string('match_key')->nullable()->after('order_id')->index();
            }
            if (!Schema::hasColumn('reconciliations', 'expected_total_net')) {
                $table->decimal('expected_total_net', 12, 2)->default(0)->after('match_key');
            }
            if (!Schema::hasColumn('reconciliations', 'actual_total_net')) {
                $table->decimal('actual_total_net', 12, 2)->default(0)->after('expected_total_net');
            }
            if (!Schema::hasColumn('reconciliations', 'diff_total_net')) {
                $table->decimal('diff_total_net', 12, 2)->default(0)->after('actual_total_net');
            }
            if (!Schema::hasColumn('reconciliations', 'diff_breakdown_json')) {
                $table->json('diff_breakdown_json')->nullable()->after('diff_total_net');
            }
            if (!Schema::hasColumn('reconciliations', 'loss_findings_json')) {
                $table->json('loss_findings_json')->nullable()->after('diff_breakdown_json');
            }
            if (!Schema::hasColumn('reconciliations', 'status')) {
                $table->enum('status', ['ok', 'warning', 'mismatch', 'missing_in_payout', 'missing_in_orders'])
                    ->default('warning')
                    ->after('loss_findings_json')
                    ->index();
            }
            if (!Schema::hasColumn('reconciliations', 'reconciled_at')) {
                $table->dateTime('reconciled_at')->nullable()->after('status');
            }
        });

        Schema::table('reconciliations', function (Blueprint $table) {
            $table->index(['payout_id', 'status']);
            $table->index(['order_id']);
        });

        Schema::table('disputes', function (Blueprint $table) {
            if (!Schema::hasColumn('disputes', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('payout_id')->constrained('orders')->nullOnDelete();
            }
            if (!Schema::hasColumn('disputes', 'amount')) {
                $table->decimal('amount', 12, 2)->default(0)->after('status');
            }
            if (!Schema::hasColumn('disputes', 'evidence_json')) {
                $table->json('evidence_json')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('disputes', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('evidence_json');
            }
            if (!Schema::hasColumn('disputes', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });

        Schema::table('disputes', function (Blueprint $table) {
            $table->index(['payout_id', 'status']);
        });

        if (!Schema::hasTable('reconciliation_rules')) {
            Schema::create('reconciliation_rules', function (Blueprint $table) {
                $table->id();
                $table->string('marketplace', 50)->index();
                $table->enum('rule_type', ['map_row_type', 'tolerance', 'loss_rule']);
                $table->string('key')->index();
                $table->json('value');
                $table->integer('priority')->default(0)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_rules');
        Schema::dropIfExists('order_financial_items');
        Schema::dropIfExists('payout_rows');

        Schema::table('disputes', function (Blueprint $table) {
            if (Schema::hasColumn('disputes', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
            if (Schema::hasColumn('disputes', 'created_by')) {
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('disputes', 'evidence_json')) {
                $table->dropColumn('evidence_json');
            }
            if (Schema::hasColumn('disputes', 'amount')) {
                $table->dropColumn('amount');
            }
            if (Schema::hasColumn('disputes', 'order_id')) {
                $table->dropConstrainedForeignId('order_id');
            }
        });

        Schema::table('reconciliations', function (Blueprint $table) {
            if (Schema::hasColumn('reconciliations', 'reconciled_at')) {
                $table->dropColumn('reconciled_at');
            }
            if (Schema::hasColumn('reconciliations', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('reconciliations', 'loss_findings_json')) {
                $table->dropColumn('loss_findings_json');
            }
            if (Schema::hasColumn('reconciliations', 'diff_breakdown_json')) {
                $table->dropColumn('diff_breakdown_json');
            }
            if (Schema::hasColumn('reconciliations', 'diff_total_net')) {
                $table->dropColumn('diff_total_net');
            }
            if (Schema::hasColumn('reconciliations', 'actual_total_net')) {
                $table->dropColumn('actual_total_net');
            }
            if (Schema::hasColumn('reconciliations', 'expected_total_net')) {
                $table->dropColumn('expected_total_net');
            }
            if (Schema::hasColumn('reconciliations', 'match_key')) {
                $table->dropColumn('match_key');
            }
            if (Schema::hasColumn('reconciliations', 'order_id')) {
                $table->dropConstrainedForeignId('order_id');
            }
        });

        Schema::table('payouts', function (Blueprint $table) {
            if (Schema::hasColumn('payouts', 'file_hash')) {
                $table->dropColumn('file_hash');
            }
            if (Schema::hasColumn('payouts', 'file_name')) {
                $table->dropColumn('file_name');
            }
            if (Schema::hasColumn('payouts', 'imported_at')) {
                $table->dropColumn('imported_at');
            }
            if (Schema::hasColumn('payouts', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
            if (Schema::hasColumn('payouts', 'payout_no')) {
                $table->dropColumn('payout_no');
            }
            if (Schema::hasColumn('payouts', 'account_id')) {
                $table->dropColumn('account_id');
            }
            if (Schema::hasColumn('payouts', 'marketplace')) {
                $table->dropColumn('marketplace');
            }
        });
    }
};
