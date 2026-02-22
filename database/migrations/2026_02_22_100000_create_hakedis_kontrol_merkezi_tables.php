<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('status', 20)->default('active')->index();
                $table->string('plan', 50)->default('free');
                $table->timestamps();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                $table->index(['tenant_id', 'role']);
            }
        });

        if (!Schema::hasTable('marketplace_integrations')) {
            Schema::create('marketplace_integrations', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name');
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('marketplace_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('marketplace_accounts', 'marketplace_integration_id')) {
                $table->foreignId('marketplace_integration_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('marketplace_integrations')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('marketplace_accounts', 'last_sync_at')) {
                $table->timestamp('last_sync_at')->nullable()->after('last_synced_at');
            }
        });

        if (!Schema::hasTable('feature_flags')) {
            Schema::create('feature_flags', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('key');
                $table->boolean('enabled')->default(false);
                $table->timestamps();

                $table->unique(['tenant_id', 'key']);
            });
        }

        if (!Schema::hasTable('settlement_rules')) {
            Schema::create('settlement_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('marketplace_integration_id')->constrained('marketplace_integrations')->cascadeOnDelete();
                $table->json('ruleset');
                $table->timestamps();

                $table->unique(['tenant_id', 'marketplace_integration_id'], 'settlement_rules_tenant_marketplace_unique');
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'marketplace_integration_id')) {
                $table->foreignId('marketplace_integration_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('marketplace_integrations')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'marketplace_account_id')) {
                $table->foreignId('marketplace_account_id')
                    ->nullable()
                    ->after('marketplace_integration_id')
                    ->constrained('marketplace_accounts')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'totals')) {
                $table->json('totals')->nullable()->after('currency');
            }
            if (!Schema::hasColumn('orders', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('totals');
            }
        });

        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->string('sku')->index();
                $table->string('variant_id')->nullable()->index();
                $table->unsignedInteger('qty')->default(1);
                $table->decimal('sale_price', 18, 4)->default(0);
                $table->decimal('sale_vat', 18, 4)->default(0);
                $table->decimal('cost_price', 18, 4)->default(0);
                $table->decimal('cost_vat', 18, 4)->default(0);
                $table->decimal('commission_amount', 18, 4)->default(0);
                $table->decimal('commission_vat', 18, 4)->default(0);
                $table->decimal('shipping_amount', 18, 4)->default(0);
                $table->decimal('shipping_vat', 18, 4)->default(0);
                $table->decimal('service_fee_amount', 18, 4)->default(0);
                $table->decimal('service_fee_vat', 18, 4)->default(0);
                $table->json('discounts')->nullable();
                $table->json('penalties')->nullable();
                $table->json('calculated')->nullable();
                $table->json('raw_payload')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'order_id']);
            });
        }

        if (!Schema::hasTable('returns')) {
            Schema::create('returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->string('marketplace_return_id')->unique();
                $table->string('status');
                $table->json('amounts')->nullable();
                $table->json('raw_payload')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payouts')) {
            Schema::create('payouts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('marketplace_integration_id')->constrained('marketplace_integrations')->cascadeOnDelete();
                $table->foreignId('marketplace_account_id')->constrained('marketplace_accounts')->cascadeOnDelete();
                $table->string('payout_reference')->nullable()->index();
                $table->date('period_start');
                $table->date('period_end');
                $table->date('expected_date')->nullable();
                $table->decimal('expected_amount', 18, 4)->default(0);
                $table->decimal('paid_amount', 18, 4)->nullable();
                $table->date('paid_date')->nullable();
                $table->string('currency', 3)->default('TRY');
                $table->string('status', 30)->default('DRAFT')->index();
                $table->json('totals')->nullable();
                $table->json('raw_payload')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'marketplace_account_id', 'period_start', 'period_end']);
            });
        }

        if (!Schema::hasTable('payout_transactions')) {
            Schema::create('payout_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('payout_id')->constrained('payouts')->cascadeOnDelete();
                $table->string('type', 40)->index();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->decimal('amount', 18, 4)->default(0);
                $table->decimal('vat_amount', 18, 4)->default(0);
                $table->json('meta')->nullable();
                $table->json('raw_payload')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('reconciliations')) {
            Schema::create('reconciliations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('payout_id')->constrained('payouts')->cascadeOnDelete();
                $table->string('matched_payment_reference')->nullable();
                $table->decimal('matched_amount', 18, 4)->nullable();
                $table->date('matched_date')->nullable();
                $table->string('match_method', 20)->default('REFERENCE');
                $table->decimal('tolerance_used', 18, 4)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('disputes')) {
            Schema::create('disputes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('payout_id')->constrained('payouts')->cascadeOnDelete();
                $table->string('dispute_type', 40)->index();
                $table->decimal('expected_amount', 18, 4)->default(0);
                $table->decimal('actual_amount', 18, 4)->default(0);
                $table->decimal('diff_amount', 18, 4)->default(0);
                $table->string('status', 40)->default('OPEN')->index();
                $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('evidence')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sync_jobs')) {
            Schema::create('sync_jobs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('marketplace_account_id')->constrained('marketplace_accounts')->cascadeOnDelete();
                $table->string('job_type', 20)->index();
                $table->string('status', 20)->default('queued')->index();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->json('stats')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sync_logs')) {
            Schema::create('sync_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sync_job_id')->constrained('sync_jobs')->cascadeOnDelete();
                $table->string('level', 10)->default('info');
                $table->text('message');
                $table->json('context')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('sync_jobs');
        Schema::dropIfExists('disputes');
        Schema::dropIfExists('reconciliations');
        Schema::dropIfExists('payout_transactions');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('settlement_rules');
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('marketplace_integrations');
    }
};

