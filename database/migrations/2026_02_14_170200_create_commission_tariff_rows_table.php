<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_tariff_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained('commission_tariff_uploads')->cascadeOnDelete();
            $table->unsignedInteger('row_no');
            $table->json('raw');
            $table->string('product_match_key')->nullable();
            $table->string('variant_match_key')->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $table->decimal('range1_min', 12, 2)->nullable();
            $table->decimal('range1_max', 12, 2)->nullable();
            $table->decimal('c1_percent', 5, 2)->nullable();

            $table->decimal('range2_min', 12, 2)->nullable();
            $table->decimal('range2_max', 12, 2)->nullable();
            $table->decimal('c2_percent', 5, 2)->nullable();

            $table->decimal('range3_min', 12, 2)->nullable();
            $table->decimal('range3_max', 12, 2)->nullable();
            $table->decimal('c3_percent', 5, 2)->nullable();

            $table->decimal('range4_min', 12, 2)->nullable();
            $table->decimal('range4_max', 12, 2)->nullable();
            $table->decimal('c4_percent', 5, 2)->nullable();

            $table->string('status')->default('unmatched');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['upload_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_tariff_rows');
    }
};
