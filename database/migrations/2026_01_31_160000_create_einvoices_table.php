<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('source_type')->default('order');
            $table->unsignedBigInteger('source_id');

            $table->string('marketplace')->nullable();
            $table->string('marketplace_order_no')->nullable();

            $table->enum('status', ['draft', 'issued', 'sent', 'accepted', 'rejected', 'cancelled', 'refunded'])->default('draft');
            $table->enum('type', ['sale', 'return', 'credit_note'])->default('sale');

            $table->string('invoice_no')->nullable();
            $table->timestamp('issued_at')->nullable();

            $table->string('currency', 3)->default('TRY');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);

            $table->string('buyer_name')->nullable();
            $table->string('buyer_email')->nullable();
            $table->string('buyer_phone')->nullable();

            $table->json('billing_address_json')->nullable();
            $table->json('shipping_address_json')->nullable();

            $table->string('provider')->nullable();
            $table->string('provider_invoice_id')->nullable();
            $table->string('provider_status')->nullable();
            $table->json('provider_payload_json')->nullable();

            $table->string('pdf_path')->nullable();
            $table->string('xml_path')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['source_type', 'source_id']);
            $table->index(['marketplace', 'marketplace_order_no']);
            $table->unique(['user_id', 'invoice_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_invoices');
    }
};
