<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('marketplaces', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // Trendyol, Hepsiburada, N11 vb.
        $table->string('code')->unique(); // trendyol, hepsiburada, n11
        $table->string('api_url')->nullable(); // API base URL
        $table->boolean('is_active')->default(true);
        $table->json('settings')->nullable(); // Ek ayarlar için
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplaces');
    }
};
