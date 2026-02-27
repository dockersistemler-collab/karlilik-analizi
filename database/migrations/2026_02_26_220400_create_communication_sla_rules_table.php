<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_sla_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->enum('channel', ['question', 'message', 'review', 'return']);
            $table->integer('sla_minutes');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_sla_rules');
    }
};

