<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_invoice_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('einvoice_id')->constrained('e_invoices')->cascadeOnDelete();
            $table->string('type');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['einvoice_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_invoice_events');
    }
};
