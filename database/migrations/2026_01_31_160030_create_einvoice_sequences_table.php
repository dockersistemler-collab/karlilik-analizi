<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('year');
            $table->string('prefix');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'year', 'prefix']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_invoice_sequences');
    }
};
