<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('e_invoices', function (Blueprint $table) {
            $table->foreignId('parent_invoice_id')
                ->nullable()
                ->constrained('e_invoices')
                ->nullOnDelete()
                ->after('source_id');
        });
    }

    public function down(): void
    {
        Schema::table('e_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_invoice_id');
        });
    }
};
