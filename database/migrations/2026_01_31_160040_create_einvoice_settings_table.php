<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('active_provider_key')->nullable();
            $table->boolean('auto_draft_enabled')->default(false);
            $table->boolean('auto_issue_enabled')->default(false);
            $table->string('draft_on_status')->default('approved');
            $table->string('issue_on_status')->default('shipped');
            $table->string('prefix')->default('EA');
            $table->decimal('default_vat_rate', 5, 2)->default(20);
            $table->timestamps();

            $table->unique(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_invoice_settings');
    }
};
