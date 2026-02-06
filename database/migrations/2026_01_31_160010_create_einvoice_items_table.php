<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('einvoice_id')->constrained('e_invoices')->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(20);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['einvoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_invoice_items');
    }
};
