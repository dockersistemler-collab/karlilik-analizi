<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_tariff_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('marketplace')->nullable();
            $table->string('file_name');
            $table->string('stored_path');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->json('column_map')->nullable();
            $table->string('status')->default('uploaded');
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('matched_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_tariff_uploads');
    }
};
