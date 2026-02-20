<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_tariff_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('marketplace')->nullable();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();

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

            $table->timestamps();

            $table->unique(['marketplace', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_tariff_assignments');
    }
};
